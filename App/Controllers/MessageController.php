<?php
// App/Controllers/MessageController.php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Message;
use App\Models\Queue;

class MessageController extends Controller {
    private $messageModel;
    private $queueModel;
    private $lastMessageId = null;
    private $syncType = 'mutex';
    
    public function __construct() {
        parent::__construct();
        $this->messageModel = new Message();
        
        // Verificar se tipo de sincronização foi passado via GET/SESSION
        if (isset($_GET['sync_type'])) {
            $this->syncType = $_GET['sync_type'];
            $_SESSION['sync_type'] = $this->syncType;
        } elseif (isset($_SESSION['sync_type'])) {
            $this->syncType = $_SESSION['sync_type'];
        }
        
        $this->queueModel = new Queue($this->syncType);
    }
    
    public function index() {
        $messages = $this->messageModel->getAll();
        $queueSize = $this->queueModel->getSize();
        $queueItems = $this->queueModel->getAll();
        $syncType = $this->syncType;
        
        $this->view('home', [
            'messages' => $messages,
            'queueSize' => $queueSize,
            'queueItems' => $queueItems,
            'syncType' => $syncType
        ]);
    }
    
    public function getMessages() {
        $this->clearOutputBuffers();
        $messages = $this->messageModel->getAll();
        $this->jsonResponse([
            'status' => 'success',
            'messages' => $messages
        ]);
    }
    
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
                'queue_id' => $queued['id'],
                'sync_type' => $this->syncType
            ]);
        }
    }
    
    public function processQueue() {
        $this->clearOutputBuffers();
        
        $item = $this->queueModel->dequeue();
        
        if ($item) {
            sleep(1);
            $saved = $this->messageModel->save($item['content'], 'async_processed');
            $this->jsonResponse([
                'status' => 'processed',
                'message' => 'Mensagem processada: ' . $item['content'],
                'item' => $item,
                'sync_type' => $this->syncType
            ]);
        } else {
            $this->jsonResponse([
                'status' => 'empty',
                'message' => 'Fila vazia - nenhuma mensagem para processar'
            ]);
        }
    }
    
    public function pollingUpdate() {
        $this->clearOutputBuffers();
        
        $lastId = isset($_GET['last_id']) ? $_GET['last_id'] : null;
        $lastQueueSize = isset($_GET['last_queue_size']) ? (int)$_GET['last_queue_size'] : -1;
        
        $messages = $this->messageModel->getAll();
        $queueSize = $this->queueModel->getSize();
        
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
        $protocolo = $input['protocolo'] ?? 'grpc';
        
        $this->jsonResponse([
            'status' => 'success',
            'protocol' => 'gRPC (simulado via HTTP/JSON)',
            'message' => 'Chamada ' . strtoupper($protocolo) . ' recebida: ' . $conteudo,
            'timestamp' => date('H:i:s')
        ]);
    }
    
    // UDP Simulado (via HTTP para compatibilidade com InfinityFree)
    public function udpEnviar() {
        $this->clearOutputBuffers();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $conteudo = $input['conteudo'] ?? 'vazio';
        
        // Simular comportamento UDP (sem confirmação garantida)
        $this->jsonResponse([
            'status' => 'sent',
            'protocol' => 'UDP (Datagrama - sem confirmação garantida)',
            'message' => 'UDP enviado: ' . $conteudo,
            'timestamp' => date('H:i:s'),
            'note' => 'UDP é não-confirmável, pode haver perda de pacotes'
        ]);
    }
    
    // TCP Simulado (via HTTP com keep-alive)
    public function tcpEnviar() {
        $this->clearOutputBuffers();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $conteudo = $input['conteudo'] ?? 'vazio';
        
        // Simular comportamento TCP (com confirmação)
        $this->jsonResponse([
            'status' => 'delivered',
            'protocol' => 'TCP (Conexão confirmada - entrega garantida)',
            'message' => 'TCP entregue: ' . $conteudo,
            'timestamp' => date('H:i:s'),
            'acknowledgment' => 'ACK recebido'
        ]);
    }
    
    // Configurar tipo de sincronização (mutex/semaphore)
    public function setSyncType() {
        $this->clearOutputBuffers();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $type = $input['type'] ?? 'mutex';
        
        if ($type === 'semaphore' || $type === 'mutex') {
            $_SESSION['sync_type'] = $type;
            $this->queueModel->setSyncType($type);
            $this->jsonResponse([
                'status' => 'success',
                'sync_type' => $type,
                'message' => "Tipo de sincronização alterado para: " . strtoupper($type)
            ]);
        } else {
            $this->jsonResponse(['error' => 'Tipo inválido. Use "mutex" ou "semaphore"'], 400);
        }
    }
    
    // Obter informações da região crítica
    public function getCriticalSectionInfo() {
        $this->clearOutputBuffers();
        
        $this->jsonResponse([
            'status' => 'success',
            'current_sync_type' => $this->syncType,
            'queue_size' => $this->queueModel->getSize(),
            'available_permits' => $this->syncType === 'semaphore' ? 
                $this->queueModel->semaphore->getAvailablePermits() : 'N/A (Mutex)',
            'explanation' => [
                'critical_resource' => 'Fila de mensagens (queue.json)',
                'potential_problem' => 'Race condition - perda de mensagens ou corrupção de dados',
                'solution' => $this->syncType === 'mutex' ? 
                    'Mutex (Exclusão Mútua) usando flock() do PHP - apenas um processo acessa a fila por vez' :
                    'Semáforo binário - controle de acesso com contagem de permissões'
            ]
        ]);
    }
    
    private function clearOutputBuffers() {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_implicit_flush(true);
    }
}