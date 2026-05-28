<?php
// server/start.php - API para iniciar/parar servidor via HTTP
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$action = $_GET['action'] ?? '';

// Arquivo de controle para saber se o servidor está rodando
$pidFile = __DIR__ . '/server.pid';
$logFile = __DIR__ . '/server.log';

if ($action === 'start') {
    // Verificar se já está rodando
    if (file_exists($pidFile)) {
        $pid = file_get_contents($pidFile);
        // Verificar se o processo ainda existe
        $output = [];
        exec("tasklist /FI \"PID eq $pid\" 2>NUL", $output);
        if (count($output) > 1) {
            echo json_encode(['status' => 'already_running', 'message' => 'Servidor já está rodando']);
            exit;
        }
    }
    
    // Iniciar servidor em background
    $command = 'start /B php ' . __DIR__ . '/server.php > ' . $logFile . ' 2>&1 &';
    exec($command);
    
    // Aguardar e capturar PID
    sleep(2);
    
    // Tentar encontrar o PID do PHP
    $output = [];
    exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV /NH', $output);
    foreach ($output as $line) {
        if (strpos($line, 'php.exe') !== false) {
            $parts = str_getcsv($line);
            $pid = $parts[1] ?? null;
            if ($pid) {
                file_put_contents($pidFile, $pid);
                break;
            }
        }
    }
    
    echo json_encode([
        'status' => 'started', 
        'message' => 'Servidor iniciado com sucesso!',
        'pid' => file_exists($pidFile) ? file_get_contents($pidFile) : 'unknown'
    ]);
    exit;
}

if ($action === 'stop') {
    if (file_exists($pidFile)) {
        $pid = file_get_contents($pidFile);
        exec("taskkill /F /PID $pid 2>NUL");
        unlink($pidFile);
        echo json_encode(['status' => 'stopped', 'message' => 'Servidor parado']);
    } else {
        // Matar todos os processos PHP como fallback
        exec("taskkill /F /IM php.exe 2>NUL");
        echo json_encode(['status' => 'stopped', 'message' => 'Todos os servidores PHP foram parados']);
    }
    exit;
}

if ($action === 'status') {
    $isRunning = false;
    if (file_exists($pidFile)) {
        $pid = file_get_contents($pidFile);
        $output = [];
        exec("tasklist /FI \"PID eq $pid\" 2>NUL", $output);
        $isRunning = count($output) > 1;
    }
    
    // Verificar se as portas estão ouvindo
    $websocketOpen = false;
    $grpcOpen = false;
    
    $fp = @fsockopen('localhost', 8080, $errno, $errstr, 1);
    if ($fp) { $websocketOpen = true; fclose($fp); }
    
    $fp = @fsockopen('localhost', 50052, $errno, $errstr, 1);
    if ($fp) { $grpcOpen = true; fclose($fp); }
    
    echo json_encode([
        'status' => $isRunning ? 'running' : 'stopped',
        'websocket' => $websocketOpen,
        'grpc' => $grpcOpen,
        'message' => $isRunning ? 'Servidor online' : 'Servidor offline'
    ]);
    exit;
}

echo json_encode(['error' => 'Ação inválida']);