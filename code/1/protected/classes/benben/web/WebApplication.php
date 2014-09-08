<?php

namespace benben\web;

use benben\Benben;
use benben\base\HttpException;
use benben\web\WidgetFactory;
use benben\db\connection\DbConnection;
use benben\base\WebConfig;
use benben\module\Module;
use benben\base\Application;


/**
 * 
 * @author benben
 *
 * @property WebConfig $config
 * @property DbConnection $db
 * @property WidgetFactory $widgetFactory
 * @property string $locale
 * @property Module $module
 * @property \benben\web\auth\WebUser $user
 */
class WebApplication extends Application
{
	protected $_controller = null;
	protected $_assetsUrl = '';
	/**
	 * Registers the core application components.
	 * This method overrides the parent implementation by registering additional core components.
	 * @see setComponents
	 */
	protected function registerCoreComponents()
	{
		parent::registerCoreComponents();
		
		$components=array(
				'user'=>array(
						'class'=>'benben\\web\\auth\\WebUser',
				),
				'session'=>array(
						'class'=>'benben\\web\\HttpSession',
				),
				'assetManager'=>array(
						'class'=>'benben\\web\\AssetManager',
				),
				'authManager'=>array(
						'class'=>'benben\\web\\auth\\PhpAuthManager',
				),
				/* 'assetManager'=>array(
						'class'=>'CAssetManager',
				), */
				'clientScript'=>array(
						'class'=>'benben\\web\\ClientScript',
				),
				'widgetFactory'=>array(
						'class'=>'benben\\web\\WidgetFactory',
				),
		);
		
		$this->setComponents($components);
	}
	
    public function processRequest()
    {
        $route = $this->getUrlManager()->parseUrl($this->getRequest());
        $this->runController($route);
    }
    
    /**
     * 创建对应模块并启动控制器运行
     * * This method will attempt to create a controller in the following order:
     * <ol>
     * <li>If the first segment is found in {@link controllerMap}, the corresponding
     * controller configuration will be used to create the controller;</li>
     * <li>If the fist segment is found to be a module ID, the corresponding module
     * will be used to create the controller;</li>
     * <li>Otherwise, it will search under the {@link controllerPath} to create
     * the coressponding controller, For example, if the route is "admin/user/create",
     * then the controller will be created using the class file "protected/controllers/admin/UserController.php".</li>
     * </ol>
     * @param string $route 格式为'[模块ID/]控制器ID/动作ID/参数名/参数值...'
     */
    public function runController($route)
    {
    	$route = trim($route,'/');
    	$moduleId = $route;
    
    	if (($pos=strpos($route, '/'))!==false)
    	{
    		$moduleId = substr($route, 0, strpos($route, '/'));
    	}
   
    	if(($this->_module = $this->getModule($moduleId))!==null)
    	{
    		$route = substr($route, $pos+1);
    	}
    	else
    	{
    		$this->_module = $this;
    	}
    	
        $route = trim($route,'/');
        $route .= '/';
        $controllerId = $route;
        
        if (($pos=strpos($route, '/'))!==false)
        {
        	$controllerId = substr($route, 0, strpos($route, '/'));
        	$route = substr($route, $pos+1);
        }
        
    	if (($controller = $this->_module->createController($controllerId))!==null)
    	{
    		$this->setController($controller);
    	    $action = $this->_actionId = (string)$this->parseActionParams($route);
    	    $controller->init();
    	    $controller->run($action);
    	}
    	else
    	{
    	    throw new HttpException(404, Benben::t('benben', 'Unable to resolve the request "{route}".',
    	    		array('{route}'=>$controllerId==='' ? $this->_module->defaultController : $controllerId)));
    	}
    }
    
    /**
     * Parses a path info into an action ID and GET variables.
     * @param string $pathInfo path info
     * @return string action ID
     */
    public function parseActionParams($pathInfo)
    {
    	if (($pos=strpos($pathInfo, '/'))!==false)
    	{
    		$manager=Benben::app()->getUrlManager();
    		$manager->parsePathInfo((string)substr($pathInfo, $pos+1));
    		$actionID=substr($pathInfo,0,$pos);
    		return $manager->caseSensitive ? $actionID : strtolower($actionID);
    	}
    	else
    	{
    		return $pathInfo;
    	}
    }
    
    /**
     * @return Controller the currently active controller
     */
    public function getController()
    {
    	return $this->_controller;
    }
    
    /**
     * @param CController $value the currently active controller
     */
    public function setController($value)
    {
    	$this->_controller=$value;
    }
    
	public function getViewPath($controller, $action)
	{
	    return $this->_module->basePath."/views/{$controller}/{$action}.php";
	}
	
	public function getLayoutPath($layout)
	{
	    return $this->_module->basePath."/views/layouts/{$layout}.php";
	}
	
	public function getWidgetFactory()
	{
	    return $this->getComponent('widgetFactory');
	}
	
	/**
	 * @return AssetManager the asset manager component
	 */
	public function getAssetManager()
	{
		return $this->getComponent('assetManager');
	}
	
	/**
	 * @return WebUser the user session information
	 */
	public function getUser()
	{
		return $this->getComponent('user');
	}
	
	/**
	 * @return HttpSession the session component
	 */
	public function getSession()
	{
		return $this->getComponent('session');
	}
	
	/**
	 * @return ClientScript the user session information
	 */
	public function getClientScript()
	{
		return $this->getComponent('clientScript');
	}
}