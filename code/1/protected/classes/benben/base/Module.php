<?php

namespace benben\base;

use benben\web\Controller;

use benben\Benben;

use benben\base\Component;

/**
 * @author benben
 * @property string $basePath
 */
class Module extends Component
{
    /**
     * @var string ID
     */
    protected $_id;
    private $_parentModule;
    /**
     * @var string 应用程序目录
     */
    protected $_basePath;
    
    /**
     * @var string
     */
    protected $_defaultController='default';
    
    /**
     * Constructor
     * @param string $id the ID of this module
     * @param Module $parent the parent module (if any)
     * @param mixed $config the module configuration. It can be either an array or
     * the path of a PHP file returning the configuration array.
     */
    public function __construct($id, $parent, $config=null)
    {
        $this->_id = $id;
        $this->_parentModule = $parent;
        
        $this->configure($config);
        $this->init();
    }
    
    protected function init()
    {
    }
    
    protected function configure($config)
    {
        // set basePath at early as possible to avoid trouble
        if (is_string($config) && is_file($config))
        {
            $config = require($config);
        }
        
        if (is_array($config))
        {
            foreach ($config as $key=>$value)
            {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Sets the aliases that are used in the module.
     * @param array $aliases list of aliases to be imported
     */
    public function setImport($aliases)
    {
    	foreach($aliases as $alias)
    	{
    		Benben::import($alias);
    	}
    }
    
	/**
     * Returns the root directory of the module.
     * @return string the root directory of the module. Defaults to the directory containing the module class.
     */
    public function getBasePath ()
    {
        if(null === $this->_basePath)
        {
            $class = new \ReflectionClass(get_class($this));
            $this->_basePath = dirname($class->getFileName());
        }
        return $this->_basePath;
    }
    
    /**
     * Sets the root directory of the module.
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the module.
     * @throws Exception if the directory does not exits.
     */
    public function setBasePath($path)
    {
        if(false === ($this->_basePath=realpath($path)) || !is_dir($this->_basePath))
        {
            throw new Exception(Benben::t('benben','Base path "{path}" is not a valid directory.'),
                    array('{path}'=>$path));
        }
    }
    
    /**
     * Creates a controller instance based on a controller id
     * @param string $controllerId
     * @return Controller
     */
    /* public function createController($controllerId)
    {
        if (empty($controllerId))
        {
            $controllerId = $this->getDefaultController();
        }
        if(!empty($this->_id))
        {
            $controllerClassName = 'application\\modules\\'.$this->_id.'\\controllers\\'.ucfirst($controllerId).'Controller';
        }
        else
        {
            $controllerClassName = 'application\\controllers\\'.ucfirst($controllerId).'Controller';
        }
        
        $classFile = CLASS_PATH.'/'.str_replace('\\', '/',$controllerClassName).'.php';
        
        if (is_file($classFile))
        {
            require $classFile;
            if (class_exists($controllerClassName))
            {
                return new $controllerClassName($this, $controllerId);
            }
        }
        
        return null;
    } */
    
    public function setDefaultController($controller)
    {
    	$this->_defaultController = $controller;
    }
    
    public function getDefaultController()
    {
    	return $this->_defaultController;
    }
    
    public function setControllerId($controllerId)
    {
    	$this->controllerId = $controllerId;
    }
    
    public function getControllerId()
    {
    	return $this->controllerId;
    }
    
    /**
     *
     * @param Controller $controller
     * @param Action $action
     */
    public function beforeControllerAction($controller, $action)
    {
    	return true;
    }
    
    /**
     *
     * @param Controller $controller
     * @param Action $action
     */
    public function afterControllerAction($controller, $action)
    {
    
    }
    
    /**
     * Returns the parent module.
     * @return Module the parent module. Null if this module does not have a parent.
     */
    public function getParentModule()
    {
    	return $this->_parentModule;
    }
}