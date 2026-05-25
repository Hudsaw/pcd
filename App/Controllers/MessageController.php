<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Message;
use App\Models\Queue;

class MessageController extends Controller {
    private $messageModel;
    private $queueModel;
    private $lastMessageId = null;
    
    public function __construct() {
        parent::__construct();
        $this->messageModel = new Message();
        $this->queueModel = new Queue();
    }
    
    public function index() {
        $messages = $this->messageModel->getAll();
        $queueSize = $this->queueModel->getSize();
        $queueItems = $this->queueModel->getAll();
        
        $this->view('home', [
            'messages' => $messages,
            'queueSize' => $queueSize,
            'queueItems' => $queueItems
        ]);
    }
    
    // GET /mensagens
    public function getMessages() {
        $this->clearOutputBuffers();
        $messages = $this->messageModel->getAll();
        $this->jsonResponse([
            'status' => 'success',
            'messages' => $messages
        ]);
    }
    
    // POST /mensagens
    public function postMessage() {
        $this->clearOutputBuffers();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['content']) || empty(trim($input['content']))) {
            $this->jsonResponse(['error' => 'Mensagem vazia'], 400);
            return;
        }
        
        $type = $input['type'] ?? 'sync';
        $content = trim($input['content']);
        
        if ($type === 'sync') {
            $message = $this->messageModel->save($content, 'sync');
            $this->jsonResponse([
                'status' => 'success',
                'type' => 'sync',
                'response' => 'Resposta imediata - comunicação SÍNCRONA',
                'message' => $message
            ]);
        } else {
            $queued = $this->queueModel->enqueue($content, 'async');
            $this->jsonResponse([
                'status' => 'queued',
                'type' => 'async',
                'queue_position' => $this->queueModel->getSize(),
                'message' => 'Mensagem enfileirada para processamento posterior',
                'queue_id' => $queued['id']
            ]);
        }
    }
    
    // POST /processar-fila
    public function processQueue() {
        $this->clearOutputBuffers();
        
        $item = $this->queueModel->dequeue();
        
        if ($item) {
            sleep(1);
            $saved = $this->messageModel->save($item['content'], 'async_processed');
            $this->jsonResponse([
                'status' => 'processed',
                'message' => 'Mensagem processada: ' . $item['content'],
                'item' => $item
            ]);
        } else {
            $this->jsonResponse([
                'status' => 'empty',
                'message' => 'Fila vazia - nenhuma mensagem para processar'
            ]);
        }
    }
    
    // NOVO: GET /polling/atualizar (substitui SSE)
    public function pollingUpdate() {
        $this->clearOutputBuffers();
        
        $lastId = isset($_GET['last_id']) ? $_GET['last_id'] : null;
        $lastQueueSize = isset($_GET['last_queue_size']) ? (int)$_GET['last_queue_size'] : -1;
        
        $messages = $this->messageModel->getAll();
        $queueSize = $this->queueModel->getSize();
        
        // Verificar novas mensagens
        $newMessages = [];
        if ($lastId) {
            foreach ($messages as $msg) {
                if ($msg['id'] > $lastId) {
                    $newMessages[] = $msg;
                }
            }
        } elseif (!empty($messages)) {
            $newMessages = $messages;
        }
        
        $response = [
            'status' => 'success',
            'new_messages' => $newMessages,
            'queue_size' => $queueSize,
            'last_message_id' => !empty($messages) ? end($messages)['id'] : null
        ];
        
        // Indicar se houve mudança na fila
        if ($queueSize != $lastQueueSize) {
            $response['queue_changed'] = true;
        }
        
        $this->jsonResponse($response);
    }
    
    // gRPC simulado
    public function grpcEnviar() {
        $this->clearOutputBuffers();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $conteudo = $input['conteudo'] ?? 'vazio';
        
        $this->jsonResponse([
            'status' => 'success',
            'protocol' => 'gRPC (simulado via HTTP/JSON)',
            'message' => 'Chamada gRPC recebida: ' . $conteudo,
            'timestamp' => date('H:i:s')
        ]);
    }
    
    private function clearOutputBuffers() {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_implicit_flush(true);
    }
}