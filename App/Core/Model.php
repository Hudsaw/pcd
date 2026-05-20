<?php
namespace App\Core;

class Model
{
    protected $dataFile;
    
    public function __construct($filename)
    {
        $dataDir = __DIR__ . '/../../data';
        
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
        
        $this->dataFile = $dataDir . '/' . $filename . '.json';
        
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([]));
        }
    }
    
    protected function readData()
    {
        if (!file_exists($this->dataFile)) {
            return [];
        }
        $content = file_get_contents($this->dataFile);
        $data = json_decode($content, true);
        return $data ?: [];
    }
    
    protected function writeData($data)
    {
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}