<?php
namespace benben\base;

abstract class ApplicationComponent extends Component implements IApplicationComponent
{
    protected $_isInitialized = false;
    
    public function getIsInitialized()
    {
        return $this->_isInitialized;
    }
    
    public function init()
    {
        
    }
    
}