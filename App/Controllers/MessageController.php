<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Message;
use App\Models\Queue;

class MessageController extends Controller {
    private $messageModel;
    private $queueModel;
    
    public function __construct() {
        parent::__construct(); // Chama o construtor da classe pai
        $this->messageModel = new Message();
        $this->queueModel = new Queue();
    }
    
    // ==================== VIEW PRINCIPAL ====================
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
    
    // ==================== API REST ====================
    // GET /mensagens
    public function getMessages() {
        // Limpar qualquer output buffer
        $this->clearOutputBuffers();
        
        $messages = $this->messageModel->getAll();
        $this->jsonResponse([
            'status' => 'success',
            'messages' => $messages
        ]);
    }
    
    // POST /mensagens
    public function postMessage() {
        // Limpar qualquer output buffer
        $this->clearOutputBuffers();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['content']) || empty(trim($input['content']))) {
            $this->jsonResponse(['error' => 'Mensagem vazia'], 400);
            return;
        }
        
        $type = $input['type'] ?? 'sync';
        $content = trim($input['content']);
        
        if ($type === 'sync') {
            // SÍNCRONO: processa e responde imediatamente
            $message = $this->messageModel->save($content, 'sync');
            $this->jsonResponse([
                'status' => 'success',
                'type' => 'sync',
                'response' => 'Resposta imediata - comunicação SÍNCRONA',
                'message' => $message
            ]);
        } else {
            // ASSÍNCRONO: entra na fila
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
    
    // ==================== PROCESSAR FILA (Assíncrono) ====================
    public function processQueue() {
        $this->clearOutputBuffers();
        
        $item = $this->queueModel->dequeue();
        
        if ($item) {
            // Simula processamento
            sleep(1);
            // Salva como processada
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
    
    // ==================== SSE (SERVER-SENT EVENTS) ====================
    public function sseStream() {
        // Limpar todos os buffers antes de enviar headers
        $this->clearOutputBuffers();
        
        // Headers para SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        
        // Iniciar com uma mensagem de conexão
        echo "data: " . json_encode(['type' => 'connected', 'message' => 'SSE Conectado']) . "\n\n";
        flush();
        
        $lastMessageId = null;
        $lastQueueSize = $this->queueModel->getSize();
        
        // Loop infinito
        while (true) {
            // Verifica novas mensagens
            $messages = $this->messageModel->getAll();
            $messageCount = count($messages);
            $lastMessage = $messageCount > 0 ? $messages[$messageCount - 1] : null;
            
            if ($lastMessage && $lastMessage['id'] !== $lastMessageId) {
                $lastMessageId = $lastMessage['id'];
                echo "data: " . json_encode([
                    'type' => 'new_message',
                    'content' => $lastMessage['content'],
                    'timestamp' => $lastMessage['timestamp']
                ]) . "\n\n";
                flush();
            }
            
            // Verifica tamanho da fila
            $currentQueueSize = $this->queueModel->getSize();
            if ($currentQueueSize != $lastQueueSize) {
                $lastQueueSize = $currentQueueSize;
                echo "data: " . json_encode([
                    'type' => 'queue_status',
                    'size' => $currentQueueSize
                ]) . "\n\n";
                flush();
            }
            
            sleep(1);
            
            // Verifica se cliente desconectou
            if (connection_status() != CONNECTION_NORMAL) {
                break;
            }
        }
    }
    
    // ==================== gRPC (SIMULADO) ====================
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
    
    // Método auxiliar para limpar buffers
    private function clearOutputBuffers() {
        // Limpa todos os buffers de saída
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Desliga o buffer automático
        ob_implicit_flush(true);
    }
    
    // Remova o método jsonResponse() daqui - ele já existe na classe pai Controller
    
    public function sseTeste() {
        $this->clearOutputBuffers();
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        $i = 0;
        while ($i < 20) {
            $i++;
            echo "data: " . json_encode(['time' => date('H:i:s'), 'count' => $i]) . "\n\n";
            flush();
            sleep(1);
        }
    }
}