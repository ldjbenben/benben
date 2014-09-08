<?php
namespace benben\log;

class FileLogRouter extends LogRouter
{
    /**
     * 日志文件
     * @var string
     */
    public $file = '';
    
    public function log($logs)
    {
        $text = '';
        
        foreach ($logs as $log)
        {
            if (in_array($log['level'], $this->_level))
            {
                $text .= "Level:{$log['level']}\tCategory:{$log['category']}\tTime:{$log['timestamp']}\r\n{$log['msg']}\n";
            }
        }
        if (!empty($text))
        {
            file_put_contents($this->file, $text);
        }
    }
    
}