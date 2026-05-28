<?php
// App/Models/Queue.php
namespace App\Models;

use App\Core\Model;
use App\Core\Mutex;
use App\Core\Semaphore;

class Queue extends Model {
    private $syncType = 'mutex'; // mutex ou semaphore
    private $mutex;
    private $semaphore;
    
    public function __construct($syncType = 'mutex') {
        parent::__construct('queue');
        $this->syncType = $syncType;
        $this->mutex = new Mutex('message_queue');
        $this->semaphore = new Semaphore('message_queue', 1);
    }
    
    public function setSyncType($type) {
        $this->syncType = $type;
    }
    
    public function enqueue($content, $type) {
        if ($this->syncType === 'semaphore') {
            return $this->semaphore->synchronized(function() use ($content, $type) {
                return $this->doEnqueue($content, $type);
            });
        } else {
            return $this->mutex->synchronized(function() use ($content, $type) {
                return $this->doEnqueue($content, $type);
            });
        }
    }
    
    private function doEnqueue($content, $type) {
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
    }
    
    public function dequeue() {
        if ($this->syncType === 'semaphore') {
            return $this->semaphore->synchronized(function() {
                return $this->doDequeue();
            });
        } else {
            return $this->mutex->synchronized(function() {
                return $this->doDequeue();
            });
        }
    }
    
    private function doDequeue() {
        $queue = $this->readData();
        if (empty($queue)) return null;
        $item = array_shift($queue);
        $this->writeData($queue);
        return $item;
    }
    
    public function getSize() {
        return count($this->readData());
    }
    
    public function getAll() {
        return $this->readData();
    }
}