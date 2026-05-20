<?php
namespace App\Core;

class Controller {
    public function __construct() {
    }
    
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function view($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../../Public/views/' . $view . '.php';
    }
}