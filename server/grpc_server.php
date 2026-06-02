<?php
// server/grpc_http.php - Versão usando PHP built-in server

$dataDir = __DIR__ . '/../data';
if (!file_exists($dataDir)) mkdir($dataDir, 0777, true);

// Configurar CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$path = $_SERVER['REQUEST_URI'] ?? '';

if ($path === '/grpc' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $content = $input['content'] ?? '';
    
    if (empty($content)) {
        echo json_encode(['status' => 'error', 'message' => 'Mensagem vazia']);
        exit();
    }
    
    // Salvar mensagem
    $file = $dataDir . '/messages.json';
    $messages = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    $message = [
        'id' => uniqid(),
        'content' => $content,
        'type' => 'grpc',
        'timestamp' => date('H:i:s')
    ];
    $messages[] = $message;
    file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'status' => 'success',
        'message' => '✅ Mensagem via gRPC!',
        'content' => $content,
        'timestamp' => date('H:i:s'),
        'protocol' => 'gRPC/HTTP2'
    ]);
    
} elseif ($path === '/health') {
    echo json_encode(['status' => 'ok', 'service' => 'grpc-http']);
    
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}