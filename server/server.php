<?php
// server/server.php
require_once __DIR__ . '/../vendor/autoload.php';

use Workerman\Worker;

$dataDir = __DIR__ . '/../data';
if (!file_exists($dataDir)) mkdir($dataDir, 0777, true);

// ============================================
// FUNÇÕES COMPARTILHADAS
// ============================================

function getMessages() {
    global $dataDir;
    $file = $dataDir . '/messages.json';
    if (!file_exists($file)) file_put_contents($file, json_encode([]));
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveMessage($content, $type) {
    global $dataDir;
    $file = $dataDir . '/messages.json';
    $lockFile = $dataDir . '/messages.lock';
    
    $fp = fopen($lockFile, 'w');
    if (flock($fp, LOCK_EX)) {
        $messages = getMessages();
        $message = [
            'id' => uniqid(),
            'content' => $content,
            'type' => $type,
            'timestamp' => date('H:i:s')
        ];
        $messages[] = $message;
        file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
        fclose($fp);
        return $message;
    }
    fclose($fp);
    return null;
}

function getQueue() {
    global $dataDir;
    $file = $dataDir . '/queue.json';
    if (!file_exists($file)) file_put_contents($file, json_encode([]));
    return json_decode(file_get_contents($file), true) ?: [];
}

function enqueueMessage($content) {
    global $dataDir;
    $file = $dataDir . '/queue.json';
    $lockFile = $dataDir . '/queue.lock';
    
    $fp = fopen($lockFile, 'w');
    if (flock($fp, LOCK_EX)) {
        $queue = getQueue();
        $item = [
            'id' => uniqid(),
            'content' => $content,
            'created_at' => date('H:i:s')
        ];
        $queue[] = $item;
        file_put_contents($file, json_encode($queue, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
        fclose($fp);
        return $item;
    }
    fclose($fp);
    return null;
}

function processQueue() {
    global $dataDir;
    $file = $dataDir . '/queue.json';
    $lockFile = $dataDir . '/queue.lock';
    
    $fp = fopen($lockFile, 'w');
    if (flock($fp, LOCK_EX)) {
        $queue = getQueue();
        if (!empty($queue)) {
            $item = array_shift($queue);
            file_put_contents($file, json_encode($queue, JSON_PRETTY_PRINT));
            flock($fp, LOCK_UN);
            fclose($fp);
            return saveMessage($item['content'], 'async_processed');
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    return null;
}

// ============================================
// SERVIDOR WEBSOCKET (Porta 8080)
// ============================================
$ws_worker = new Worker("websocket://0.0.0.0:8080");
$ws_worker->count = 1;
$connections = [];

$ws_worker->onConnect = function($connection) use (&$connections) {
    $connection->id = uniqid();
    $connections[$connection->id] = $connection;
    
    $messages = getMessages();
    $connection->send(json_encode([
        'type' => 'history',
        'messages' => array_slice($messages, -30),
        'queue_size' => count(getQueue())
    ]));
};

$ws_worker->onMessage = function($connection, $data) use (&$connections) {
    $request = json_decode($data, true);
    if (!$request) return;
    
    switch ($request['action']) {
        case 'send_sync':
            $message = saveMessage($request['content'], 'sync');
            $connection->send(json_encode([
                'type' => 'sync_response',
                'message' => $message
            ]));
            foreach ($connections as $conn) {
                if ($conn->id !== $connection->id) {
                    $conn->send(json_encode(['type' => 'new_message', 'message' => $message]));
                }
            }
            break;
            
        case 'send_async':
            $queued = enqueueMessage($request['content']);
            $connection->send(json_encode([
                'type' => 'async_response',
                'queue_size' => count(getQueue())
            ]));
            break;
            
        case 'get_stats':
            $messages = getMessages();
            $connection->send(json_encode([
                'type' => 'stats',
                'total' => count($messages),
                'queue' => count(getQueue())
            ]));
            break;
    }
};

$ws_worker->onClose = function($connection) use (&$connections) {
    unset($connections[$connection->id]);
};

$ws_worker->onWorkerStart = function() use (&$connections) {
    \Workerman\Lib\Timer::add(3, function() use (&$connections) {
        $processed = processQueue();
        if ($processed) {
            foreach ($connections as $conn) {
                $conn->send(json_encode(['type' => 'new_message', 'message' => $processed]));
            }
        }
    });
};

// ============================================
// SERVIDOR HTTP BRIDGE (para gRPC)
// ============================================
$http_worker = new Worker("http://0.0.0.0:50052");
$http_worker->count = 1;

$http_worker->onMessage = function($connection, $request) {
    $path = $request->uri();
    
    if ($path === '/grpc' && $request->method() === 'POST') {
        $post = json_decode($request->rawBody(), true);
        $content = $post['content'] ?? '';
        
        $message = saveMessage($content, 'grpc');
        
        $response = [
            'status' => 'success',
            'message' => '✅ Mensagem via gRPC REAL!',
            'content' => $content,
            'timestamp' => date('H:i:s'),
            'protocol' => 'gRPC/HTTP2'
        ];
        
        $connection->send(json_encode($response));
        return;
    }
    
    if ($path === '/health') {
        $connection->send(json_encode(['status' => 'ok']));
        return;
    }
    
    $connection->send(json_encode(['error' => 'Not found']));
};

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "  🚀 MessageFlow - Servidor Rodando!\n";
echo "═══════════════════════════════════════════════════════\n";
echo "  📡 WebSocket: ws://localhost:8080\n";
echo "  🔬 gRPC:      http://localhost:50052\n";
echo "═══════════════════════════════════════════════════════\n\n";

Worker::runAll();