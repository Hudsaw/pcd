<?php
// Public/index.php
session_start();

// Autoload simplificado
spl_autoload_register(function($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../App/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) return;
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) require $file;
});

// Configurar para sempre retornar JSON para APIs
$isApiRoute = false;
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// IMPORTANTE: Forçar JSON para todas as requisições que não são para HTML
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$isApiRequest = strpos($acceptHeader, 'application/json') !== false || 
                strpos($requestUri, '/mensagens') !== false ||
                strpos($requestUri, '/polling') !== false ||
                strpos($requestUri, '/set-sync-type') !== false;

// Remover base path
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/\\');
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remover query string
if (($pos = strpos($requestUri, '?')) !== false) {
    $requestUri = substr($requestUri, 0, $pos);
}

if (empty($requestUri) || $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

// Rotas da API
$apiRoutes = [
    ['pattern' => '/mensagens', 'method' => 'GET', 'handler' => 'getMessages'],
    ['pattern' => '/mensagens', 'method' => 'POST', 'handler' => 'postMessage'],
    ['pattern' => '/processar-fila', 'method' => 'POST', 'handler' => 'processQueue'],
    ['pattern' => '/polling/atualizar', 'method' => 'GET', 'handler' => 'pollingUpdate'],
    ['pattern' => '/grpc/enviar', 'method' => 'POST', 'handler' => 'grpcEnviar'],
    ['pattern' => '/udp/enviar', 'method' => 'POST', 'handler' => 'udpEnviar'],
    ['pattern' => '/tcp/enviar', 'method' => 'POST', 'handler' => 'tcpEnviar'],
    ['pattern' => '/set-sync-type', 'method' => 'POST', 'handler' => 'setSyncType'],
    ['pattern' => '/critical-section-info', 'method' => 'GET', 'handler' => 'getCriticalSectionInfo'],
];

$controller = new App\Controllers\MessageController();

foreach ($apiRoutes as $route) {
    if ($requestUri === $route['pattern'] && $method === $route['method']) {
        $handler = $route['handler'];
        $controller->$handler();
        exit;
    }
}

// Se for requisição API mas não encontrou rota
if ($isApiRequest) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Rota não encontrada: ' . $requestUri]);
    exit;
}

// Arquivos estáticos
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico)$/', $requestUri)) {
    $filePath = __DIR__ . $requestUri;
    if (file_exists($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon'
        ];
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }
        readfile($filePath);
        exit;
    }
}

// Rota principal
if ($requestUri == '/' || $requestUri == '/index.php') {
    $controller->index();
    exit;
}

// Fallback - página 404
http_response_code(404);
echo "<h1>404 - Página não encontrada</h1>";