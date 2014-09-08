<?php
namespace benben\log;

class WebLogRouter extends LogRouter
{
    public function log($logs)
    {
        $this->display('log', $logs);
    }
    
    protected function display($view, $data)
    {
        include BENBEN_PATH.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$view.'.php';
    }
    
}