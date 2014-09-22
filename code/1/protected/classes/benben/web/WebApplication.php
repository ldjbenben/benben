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
 * @property \benben\web\HttpSession $session
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
				'viewRenderer'=>array(
						'class'=>'benben\\web\\view\\BasicView',
				),
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
    /* public function runController($route, $owner = null)
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
    } */
    
    /**
     * Creates the controller and performs the specified action.
     * @param string $route the route of the current request. See {@link createController} for more details.
     * @throws HttpException if the controller could not be created.
     */
    public function runController($route)
    {
    	if(($ca=$this->createController($route))!==null)
    	{
    		list($controller,$actionID)=$ca;
    		$oldController=$this->_controller;
    		$this->_controller=$controller;
    		$controller->init();
    		$controller->run($actionID);
    		$this->_controller=$oldController;
    	}
    	else
    		throw new HttpException(404,Benben::t('benben','Unable to resolve the request "{route}".',
    				array('{route}'=>$route===''?$this->defaultController:$route)));
    }
    
    /**
     * Creates a controller instance based on a route.
     * The route should contain the controller ID and the action ID.
     * It may also contain additional GET variables. All these must be concatenated together with slashes.
     *
     * This method will attempt to create a controller in the following order:
     * <ol>
     * <li>If the first segment is found in {@link controllerMap}, the corresponding
     * controller configuration will be used to create the controller;</li>
     * <li>If the first segment is found to be a module ID, the corresponding module
     * will be used to create the controller;</li>
     * <li>Otherwise, it will search under the {@link controllerPath} to create
     * the corresponding controller. For example, if the route is "admin/user/create",
     * then the controller will be created using the class file "protected/controllers/admin/UserController.php".</li>
     * </ol>
     * @param string $route the route of the request.
     * @param WebModule $owner the module that the new controller will belong to. Defaults to null, meaning the application
     * instance is the owner.
     * @return array the controller instance and the action ID. Null if the controller class does not exist or the route is invalid.
     */
    public function createController($route,$owner=null)
    {
    	if($owner===null)
    		$owner=$this;
    	if(($route=trim($route,'/'))==='')
    		$route=$owner->defaultController;
    	$caseSensitive=$this->getUrlManager()->caseSensitive;
    
    	$route.='/';
    	
    	while(($pos=strpos($route,'/'))!==false)
    	{
    		$id=substr($route,0,$pos);
    		if(!preg_match('/^\w+$/',$id))
    			return null;
    		if(!$caseSensitive)
    			$id=strtolower($id);
    		$route=(string)substr($route,$pos+1);
    		if(!isset($basePath))  // first segment
    		{
    			if(isset($owner->controllerMap[$id]))
    			{
    				return array(
    						Benben::createComponent($owner->controllerMap[$id],$id,$owner===$this?null:$owner),
    						$this->parseActionParams($route),
    				);
    			}
    
    			if(($module=$owner->getModule($id))!==null)
    				return $this->createController($route,$module);
    
    			$basePath=$owner->getControllerPath();
    			$controllerID='';
    		}
    		else
    			$controllerID.='/';
    		$className='application\\controllers\\'.ucfirst($id).'Controller';
    		$classFile=CLASS_PATH.DIRECTORY_SEPARATOR.$className.'.php';
    		if(is_file($classFile))
    		{
    			if(!class_exists($className,false))
    				require($classFile);
    			if(class_exists($className,false) && is_subclass_of($className,'benben\\web\\Controller'))
    			{
    				$id[0]=strtolower($id[0]);
    				return array(
    						new $className($controllerID.$id,$owner===$this?null:$owner),
    						$this->parseActionParams($route),
    				);
    			}
    			return null;
    		}
    		$controllerID.=$id;
    		$basePath.=DIRECTORY_SEPARATOR.$id;
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
    		throw new Exception(Benben::t('benben','The layout path "{path}" is not a valid directory.',
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
	
	/**
	 * Returns the view renderer.
	 * If this component is registered and enabled, the default
	 * view rendering logic defined in {@link BaseController} will
	 * be replaced by this renderer.
	 * @return IViewRenderer the view renderer.
	 */
	public function getViewRenderer()
	{
		return $this->getComponent('viewRenderer');
	}
	
	/**
	 * @return string the directory that contains the controller classes. Defaults to 'protected/controllers'.
	 */
	public function getControllerPath()
	{
		if($this->_controllerPath!==null)
			return $this->_controllerPath;
		else
			return $this->_controllerPath=CLASS_PATH.DIRECTORY_SEPARATOR.'application'.DIRECTORY_SEPARATOR.'controllers';
	}
	
	/**
	 * @param string $value the directory that contains the controller classes.
	 * @throws CException if the directory is invalid
	 */
	public function setControllerPath($value)
	{
		if(($this->_controllerPath=realpath($value))===false || !is_dir($this->_controllerPath))
			throw new Exception(Benben::t('benben','The controller path "{path}" is not a valid directory.',
					array('{path}'=>$value)));
	}
	
/**
	 * The pre-filter for controller actions.
	 * This method is invoked before the currently requested controller action and all its filters
	 * are executed. You may override this method with logic that needs to be done
	 * before all controller actions.
	 * @param Controller $controller the controller
	 * @param Action $action the action
	 * @return boolean whether the action should be executed.
	 */
	public function beforeControllerAction($controller,$action)
	{
		return true;
	}
}