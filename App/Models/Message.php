<?php
namespace App\Models;

use App\Core\Model;

class Message extends Model {
    public function __construct() {
        parent::__construct('messages');
    }
    
    public function getAll() {
        return $this->readData();
    }
    
    public function save($content, $type) {
        $messages = $this->readData();
        $message = [
            'id' => uniqid(),
            'content' => $content,
            'type' => $type,
            'timestamp' => date('H:i:s')
        ];
        $messages[] = $message;
        $this->writeData($messages);
        return $message;
    }
    
    public function getLastId() {
        $messages = $this->readData();
        $last = end($messages);
        return $last ? $last['id'] : null;
    }
}