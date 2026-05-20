<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Mutex;

class Queue extends Model {
    private $mutex;
    
    public function __construct() {
        parent::__construct('queue');
        $this->mutex = new Mutex('message_queue');
    }
    
    public function enqueue($content, $type) {
        return $this->mutex->synchronized(function() use ($content, $type) {
            $queue = $this->readData();
            $item = [
                'id' => uniqid(),
                'content' => $content,
                'type' => $type,
                'status' => 'pending',
                'created_at' => date('H:i:s')
            ];
            $queue[] = $item;
            $this->writeData($queue);
            return $item;
        });
    }
    
    public function dequeue() {
        return $this->mutex->synchronized(function() {
            $queue = $this->readData();
            if (empty($queue)) return null;
            $item = array_shift($queue);
            $this->writeData($queue);
            return $item;
        });
    }
    
    public function getSize() {
        return count($this->readData());
    }
    
    public function getAll() {
        return $this->readData();
    }
}