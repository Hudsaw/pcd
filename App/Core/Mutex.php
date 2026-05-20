<?php
namespace App\Core;

class Mutex
{
    private $lockFile;
    private $fp;
    
    public function __construct($resourceName)
    {
        $this->lockFile = sys_get_temp_dir() . '/' . md5($resourceName) . '.lock';
    }
    
    public function acquire()
    {
        $this->fp = @fopen($this->lockFile, 'w');
        if ($this->fp && flock($this->fp, LOCK_EX)) {
            return true;
        }
        return false;
    }
    
    public function release()
    {
        if ($this->fp) {
            flock($this->fp, LOCK_UN);
            fclose($this->fp);
            $this->fp = null;
        }
    }
    
    public function synchronized($callback)
    {
        $this->acquire();
        $result = $callback();
        $this->release();
        return $result;
    }
    
    public function __destruct()
    {
        $this->release();
    }
}