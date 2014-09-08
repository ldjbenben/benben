<?php
namespace benben\log;

use benben\base\ApplicationComponent;

abstract class LogRouter extends ApplicationComponent implements ILogRoute
{
    protected $_level;
    
    public function init()
    {
        
    }
    
    /**
     * @param string $level
     */
    public function setLevel($level)
    {
        $this->_level = explode(',', $level);
    }
}