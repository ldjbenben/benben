<?php

namespace benben\web;

use benben\Benben;
use benben\base\HttpException;
use benben\web\WidgetFactory;
use benben\db\connection\DbConnection;
use benben\base\WebConfig;
use benben\module\Module;
use benben\base\Application;
use benben\base\Exception;


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
	protected $_assetsUrl = 'public';
	
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
    public function runController($route, $owner = null)
    {
    	$route = trim($route,'/');
    	$moduleId = $route;
    
    	if (($pos=strpos($route, '/'))!==false)
    	{
    		$moduleId = substr($route, 0, strpos($route, '/'));
    	}
   
    	if(($this->_module = $this->getModule($moduleId))!==null)
    	{
    		$owner = $this->_module;
    		$route = substr($route, $pos+1);
    	}
    	else
    	{
    		$owner = $this;
    	}
    	
        $route = trim($route,'/');
        $route .= '/';
        $controllerId = $route;
        
        if (($pos=strpos($route, '/'))!==false)
        {
        	$controllerId = substr($route, 0, strpos($route, '/'));
        	$route = substr($route, $pos+1);
        }
        
    	if (($controller = $owner->createController($controllerId))!==null)
    	{
    		$this->setController($controller);
    	    $action = $this->_actionId = (string)$this->parseActionParams($route);
    	    $controller->init();
    	    $controller->run($action);
    	}
    	else
    	{
    	    throw new HttpException(404, Benben::t('benben', 'Unable to resolve the request "{route}".',
    	    		array('{route}'=>$controllerId==='' ? $owner->defaultController : $controllerId)));
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
    
	/* public function getViewPath($controller, $action)
	{
	    return $this->_module->basePath."/views/{$controller}/{$action}.php";
	} */
    /**
     * @return string the root directory of view files. Defaults to 'protected/views'.
     */
    public function getViewPath()
    {
    	if($this->_viewPath!==null)
    		return $this->_viewPath;
    	else
    		return $this->_viewPath=$this->getBasePath().DIRECTORY_SEPARATOR.'views';
    }
    
    /**
     * @param string $path the root directory of view files.
     * @throws Exception if the directory does not exist.
     */
    public function setViewPath($path)
    {
    	if(($this->_viewPath=realpath($path))===false || !is_dir($this->_viewPath))
    		throw new Exception(Benben::t('benben','The view path "{path}" is not a valid directory.',
    				array('{path}'=>$path)));
    }
	
	/* public function getLayoutPath($layout)
	{
	    return $this->_module->basePath."/views/layouts/{$layout}.php";
	} */
    /**
     * @return string the root directory of layout files. Defaults to 'protected/views/layouts'.
     */
    public function getLayoutPath()
    {
    	if($this->_layoutPath!==null)
    		return $this->_layoutPath;
    	else
    		return $this->_layoutPath=$this->getViewPath().DIRECTORY_SEPARATOR.'layouts';
    }
    
    /**
     * @param string $path the root directory of layout files.
     * @throws CException if the directory does not exist.
     */
    public function setLayoutPath($path)
    {
    	if(($this->_layoutPath=realpath($path))===false || !is_dir($this->_layoutPath))
    		throw new Exception(Benben::t('yii','The layout path "{path}" is not a valid directory.',
    				array('{path}'=>$path)));
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
	
	public function getAssetsUrl()
	{
		return $this->_assetsUrl;
	}
	
	public function setAssetsUrl($asstesUrl)
	{
		$this->_assetsUrl = $asstesUrl;
	}
	
	/**
	 * @return ThemeManager the theme manager.
	 */
	public function getThemeManager()
	{
		return $this->getComponent('themeManager');
	}
	
	/**
	 * @return Theme the theme used currently. Null if no theme is being used.
	 */
	public function getTheme()
	{
		if(is_string($this->_theme))
			$this->_theme=$this->getThemeManager()->getTheme($this->_theme);
		return $this->_theme;
	}
	
	/**
	 * @param string $value the theme name
	 */
	public function setTheme($value)
	{
		$this->_theme=$value;
	}
}