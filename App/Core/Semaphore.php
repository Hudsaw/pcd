<?php
// App/Core/Semaphore.php
namespace App\Core;

class Semaphore
{
    private $lockFile;
    private $fp;
    private $maxPermits;
    private $permitsFile;
    
    public function __construct($resourceName, $maxPermits = 1)
    {
        $this->maxPermits = $maxPermits;
        $this->lockFile = sys_get_temp_dir() . '/' . md5($resourceName) . '.sem';
        $this->permitsFile = sys_get_temp_dir() . '/' . md5($resourceName) . '.permits';
        
        // Inicializar contador de permissões
        if (!file_exists($this->permitsFile)) {
            file_put_contents($this->permitsFile, $maxPermits);
        }
    }
    
    public function acquire($timeout = 5)
    {
        $start = time();
        while (true) {
            $fp = @fopen($this->lockFile, 'w');
            if ($fp && flock($fp, LOCK_EX)) {
                $permits = (int)file_get_contents($this->permitsFile);
                if ($permits > 0) {
                    file_put_contents($this->permitsFile, $permits - 1);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    $this->fp = $fp;
                    return true;
                }
                flock($fp, LOCK_UN);
                fclose($fp);
            }
            
            if (time() - $start >= $timeout) {
                return false;
            }
            usleep(100000); // 100ms
        }
    }
    
    public function release()
    {
        $fp = @fopen($this->lockFile, 'w');
        if ($fp && flock($fp, LOCK_EX)) {
            $permits = (int)file_get_contents($this->permitsFile);
            if ($permits < $this->maxPermits) {
                file_put_contents($this->permitsFile, $permits + 1);
            }
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
    
    public function synchronized($callback)
    {
        if ($this->acquire()) {
            try {
                $result = $callback();
                $this->release();
                return $result;
            } catch (Exception $e) {
                $this->release();
                throw $e;
            }
        }
        throw new \RuntimeException("Não foi possível adquirir o semáforo");
    }
    
    public function getAvailablePermits()
    {
        return (int)file_get_contents($this->permitsFile);
    }
}