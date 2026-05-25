<?php
// Public/index.php
session_start();

// Carregar constantes
require_once __DIR__ . '/../config/constants.php';

// Autoload
spl_autoload_register(function($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH . DS;
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', DS, $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

// Debug
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Processar rota
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remove o caminho base
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/\\');
if ($basePath == '.' || empty($basePath)) {
    $basePath = '';
}

// Remove base path da URI
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remove query string
if (($pos = strpos($requestUri, '?')) !== false) {
    $requestUri = substr($requestUri, 0, $pos);
}

// Garante que comece com /
if (empty($requestUri) || $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

// Instanciar controller
$controller = new App\Controllers\MessageController();

// Rotas
$routes = [
    // API Routes
    ['pattern' => '/mensagens', 'method' => 'GET', 'handler' => 'getMessages'],
    ['pattern' => '/mensagens', 'method' => 'POST', 'handler' => 'postMessage'],
    ['pattern' => '/processar-fila', 'method' => 'POST', 'handler' => 'processQueue'],
    ['pattern' => '/polling/atualizar', 'method' => 'GET', 'handler' => 'pollingUpdate'], // Novo endpoint
];

// Verificar rotas de API
foreach ($routes as $route) {
    if ($requestUri === $route['pattern'] && $method === $route['method']) {
        $handler = $route['handler'];
        $controller->$handler();
        exit;
    }
}

// Verificar arquivos estáticos
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico)$/', $requestUri)) {
    $filePath = PUBLIC_PATH . str_replace('/', DS, $requestUri);
    if (file_exists($filePath)) {
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon'
        ];
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }
        readfile($filePath);
        exit;
    }
}

// Rota padrão
if ($requestUri == '/' || $requestUri == '/index.php') {
    $controller->index();
    exit;
}

// 404
header('Content-Type: application/json');
http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada: ' . $requestUri]);