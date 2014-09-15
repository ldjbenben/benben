<?php

namespace benben\web;

use benben\Benben;

use benben\base\HttpException;

use benben\web\view\View;
use benben\web\actions\Action;
use benben\web\actions\InlineAction;
use benben\base\Exception;
use benben\filters\FilterChain;
use benben\web\auth\AccessControlFilter;
use benben\collections\CStack;

/** 
 * @author benben
 * @property View $view
 * @property Module $module
 */
class Controller extends BaseController
{
	/**
	 * Name of the hidden field storing persistent page states.
	 */
	const STATE_INPUT_NAME='BENBEN_PAGE_STATE';
	
	/**
	 * 控制器ID
	 * @var string
	 */
	protected $_id = '';
	/**
	 * @var Action
	 */
	private $_action;
	public $defaultAction = 'index';
    /**
     * @var View
     */
    protected $_view = null;
    /**
     * 模板布局文件
     * @var string
     */
    protected $_layout = 'main';
    /**
     * 控制器所属的模块
     * @var \benben\base\Module
     */
    protected $_module = null;
    
    public function __construct($module, $id)
    {
    	$this->_module = $module;
    	$this->_id = $id;
    }
    
	/**
	 * Initializes the controller.
	 * This method is called by the application before the controller starts to execute.
	 * You may override this method to perform the needed initialization for the controller.
	 */
	public function init()
	{
	}
    
    /**
     * Returns the filter configurations.
     *
     * By overriding this method, child classes can specify filters to be applied to actions.
     *
     * This method returns an array of filter specifications. Each array element specify a single filter.
     *
     * For a method-based filter (called inline filter), it is specified as 'FilterName[ +|- Action1, Action2, ...]',
     * where the '+' ('-') operators describe which actions should be (should not be) applied with the filter.
     *
     * For a class-based filter, it is specified as an array like the following:
     * <pre>
     * array(
     *     'FilterClass[ +|- Action1, Action2, ...]',
     *     'name1'=>'value1',
     *     'name2'=>'value2',
     *     ...
     * )
     * </pre>
     * where the name-value pairs will be used to initialize the properties of the filter.
     *
     * Note, in order to inherit filters defined in the parent class, a child class needs to
     * merge the parent filters with child filters using functions like array_merge().
     *
     * @return array a list of filter configurations.
     * @see Filter
     */
    public function filters()
    {
    	return array();
    }

    /**
     * The filter method for 'accessControl' filter.
     * This filter is a wrapper of {@link AccessControlFilter}.
     * To use this filter, you must override {@link accessRules} method.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     */
    public function filterAccessControl($filterChain)
    {
    	$filter=new AccessControlFilter();
    	$filter->setRules($this->accessRules());
    	$filter->filter($filterChain);
    }
    
    /**
     * Returns a list of external action classes.
     * Array keys are action IDs, and array values are the corresponding action
     * class or arrays representing the configuration of the actions, such as the following,
     * <pre>
     * return array(
	 *     'action1'=>'Action1Class',
	 *     'action2'=>array(
	 *         'class'=>'xxx\Action2Class',
	 *         'property1'=>'value1',
	 *         'property2'=>'value2',
	 *     ),
	 * );
     * </pre>
     * Derived classes may override this method to declare external actions.
     * 
     * Note, in order to inherit actions, defined in the parent class, a child class needs to 
     * merge the parent actions with child actions using functions like array_merge().
     * 
     You may import actions from an action provider
	 * (such as a widget, see {@link CWidget::actions}), like the following:
	 * <pre>
	 * return array(
	 *     ...other actions...
	 *     // import actions declared in ProviderClass::actions()
	 *     // the action IDs will be prefixed with 'pro.'
	 *     'pro.'=>'path.to.ProviderClass',
	 *     // similar as above except that the imported actions are
	 *     // configured with the specified initial property values
	 *     'pro2.'=>array(
	 *         'class'=>'path.to.ProviderClass',
	 *         'action1'=>array(
	 *             'property1'=>'value1',
	 *         ),
	 *         'action2'=>array(
	 *             'property2'=>'value2',
	 *         ),
	 *     ),
	 * )
	 * </pre>
	 *
	 * In the above, we differentiate action providers from other action
	 * declarations by the array keys. For action providers, the array keys
	 * must contain a dot. As a result, an action ID 'pro2.action1' will
	 * be resolved as the 'action1' action declared in the 'ProviderClass'.
	 *
	 * @return array list of external action classes
	 * @see createAction
     */
    public function actions()
    {
    	return array();
    }
    
    /**
     * Runs the named action.
     * Filters specified via {@link filters()} will be applied.
     * @param string $actionID action ID
     * @throws HttpException if the action does not exist or the action name is not proper.
     * @see filters
     * @see createAction
     * @see runAction
     */
    public function run($actionID)
    {
    	if(($action=$this->createAction($actionID))!==null)
    	{
    		if(($parent=$this->getModule())===null)
    			$parent=Benben::app();
    		if($parent->beforeControllerAction($this,$action))
    		{
    			$this->runActionWithFilters($action,$this->filters());
    			$parent->afterControllerAction($this,$action);
    		}
    	}
    	else
    		$this->missingAction($actionID);
    }
    
    /**
     * Handles the request whose action is not recognized.
     * This method is invoked when the controller cannot find the requested action.
     * The default implementation simply throws an exception.
     * @param string $actionID the missing action name
     * @throws HttpException whenever this method is invoked
     */
    public function missingAction($actionID)
    {
    	throw new HttpException(404,Benben::t('benben','The system is unable to find the requested action "{action}".',
    			array('{action}'=>$actionID==''?$this->defaultAction:$actionID)));
    }
    
    /**
     * Runs the action after passing through all filters.
     * This method is invoked by {@link runActionWithFilters} after all possible filters have been executed
     * and the action starts to run.
     * @param Action $action action to run
     */
    public function runAction($action)
    {
    	$priorAction=$this->_action;
    	$this->_action=$action;
    	if($this->beforeAction($action))
    	{
    		if($action->runWithParams($this->getActionParams())===false)
    			$this->invalidActionParams($action);
    		else
    			$this->afterAction($action);
    	}
    	$this->_action=$priorAction;
    }
    
    /**
     * Returns the request parameters that will be used for action parameter binding.
     * By default, this method will return $_GET. You may override this method if you
     * want to use other request parameters (e.g. $_GET+$_POST).
     * @return array the request parameters to be used for action parameter binding
     */
    public function getActionParams()
    {
    	return $_GET;
    }
    
    /**
     * This method is invoked when the request parameters do not satisfy the requirement of the specified action.
     * The default implementation will throw a 400 HTTP exception.
     * @param Action $action the action being executed
     */
    public function invalidActionParams($action)
    {
    	throw new HttpException(400,Benben::t('benben','Your request is invalid.'));
    }
    
    /**
     * Creates the action instance based on the action name.
     * The action can be either an inline action or an object.
     * The latter is created by looking up the action map specified in {@link actions}.
     * @param string $actionID ID of the action. If empty, the {@link defaultAction default action} will be used.
     * @return Action the action instance, null if the action does not exist.
     * @see actions
     */
    public function createAction($actionID)
    {
    	if(empty($actionID))
    		$actionID=$this->defaultAction;
    	if(method_exists($this,'action'.$actionID)) // we have actions method
    	{
    		return new InlineAction($this,$actionID);
    	}
    	else
    	{
    		$action=$this->createActionFromMap($this->actions(),$actionID,$actionID);
    		if($action!==null && !method_exists($action,'run'))
    			throw new Exception(Benben::t('benben', 'Action class {class} must implement the "run" method.', array('{class}'=>get_class($action))));
    		return $action;
    	}
    }
    
    /**
     * Creates the action instance based on the action map.
     * This method will check to see if the action ID appears in the given
     * action map. If so, the corresponding configuration will be used to
     * create the action instance.
     * @param array $actionMap the action map
     * @param string $actionID the action ID that has its prefix stripped off
     * @param string $requestActionID the originally requested action ID
     * @param array $config the action configuration that should be applied on top of the configuration specified in the map
     * @return Action the action instance, null if the action does not exist.
     */
    protected function createActionFromMap($actionMap,$actionID,$requestActionID,$config=array())
    {
    	if(($pos=strpos($actionID,'.'))===false && isset($actionMap[$actionID]))
    	{
    		$baseConfig=is_array($actionMap[$actionID]) ? $actionMap[$actionID] : array('class'=>$actionMap[$actionID]);
    		return Benben::createComponent(empty($config)?$baseConfig:array_merge($baseConfig,$config),$this,$requestActionID);
    	}
    	else if($pos===false)
    		return null;
    
    	// the action is defined in a provider
    	$prefix=substr($actionID,0,$pos+1);
    	if(!isset($actionMap[$prefix]))
    		return null;
    	$actionID=(string)substr($actionID,$pos+1);
    
    	$provider=$actionMap[$prefix];
    	if(is_string($provider))
    		$providerType=$provider;
    	else if(is_array($provider) && isset($provider['class']))
    	{
    		$providerType=$provider['class'];
    		if(isset($provider[$actionID]))
    		{
    			if(is_string($provider[$actionID]))
    				$config=array_merge(array('class'=>$provider[$actionID]),$config);
    			else
    				$config=array_merge($provider[$actionID],$config);
    		}
    	}
    	else
    		throw new Exception(Benben::t('benben','Object configuration must be an array containing a "class" element.'));
    
    	$class=Benben::import($providerType,true);
    	$map=call_user_func(array($class,'actions'));
    
    	return $this->createActionFromMap($map,$actionID,$requestActionID,$config);
    }
    
    /**
     * Runs an action with the specified filters.
     * A filter chain will be created based on the specified filters
     * and the action will be executed then.
     * @param Action $action the action to be executed.
     * @param array $filters list of filters to be applied to the action.
     * @see filters
     * @see createAction
     * @see runAction
     */
    public function runActionWithFilters($action,$filters)
    {
    	if(empty($filters))
    		$this->runAction($action);
    	else
    	{
    		$priorAction=$this->_action;
    		$this->_action=$action;
    		FilterChain::create($this,$action,$filters)->run();
    		$this->_action=$priorAction;
    	}
    }
    
    /**
     * Redirects the browser to the specified URL or route (controller/action).
     * @param mixed $url the URL to be redirected to. If the parameter is an array,
     * the first element must be a route to a controller action and the rest
     * are GET parameters in name-value pairs.
     * @param boolean $terminate whether to terminate the current application after calling this method. Defaults to true.
     * @param integer $statusCode the HTTP status code. Defaults to 302. See {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
     * for details about HTTP status code.
     */
    public function redirect($url,$terminate=true,$statusCode=302)
    {
    	if(is_array($url))
    	{
    		$route=isset($url[0]) ? $url[0] : '';
    		$url=$this->createUrl($route,array_splice($url,1));
    	}
    	
    	Benben::app()->getRequest()->redirect($url,$terminate,$statusCode);
    }
    
    /**
     * Creates a relative URL for the specified action defined in this controller.
     * @param string $route the URL route. This should be in the format of 'ControllerID/ActionID'.
     * If the ControllerID is not present, the current controller ID will be prefixed to the route.
     * If the route is empty, it is assumed to be the current action.
     * If the controller belongs to a module, the {@link WebModule::getId module ID}
     * will be prefixed to the route. (If you do not want the module ID prefix, the route should start with a slash '/'.)
     * @param array $params additional GET parameters (name=>value). Both the name and value will be URL-encoded.
     * If the name is '#', the corresponding value will be treated as an anchor
     * and will be appended at the end of the URL.
     * @param string $ampersand the token separating name-value pairs in the URL.
     * @return string the constructed URL
     */
    public function createUrl($route,$params=array(),$ampersand='&')
    {
    	if($route==='')
    		$route=$this->getId().'/'.$this->getAction()->getId();
    	else if(strpos($route,'/')===false)
    		$route=$this->getId().'/'.$route;
    	if($route[0]!=='/' && ($module=$this->getModule())!==null)
    		$route=$module->getId().'/'.$route;
    	return Benben::app()->createUrl(trim($route,'/'),$params,$ampersand);
    }
    
    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * You may override this method to do last-minute preparation for the action.
     * @param Action $action the action to be executed.
     * @return boolean whether the action should be executed.
     */
    protected function beforeAction($action)
    {
    	return true;
    }
    
    /**
     * This method is invoked right after an action is executed.
     * You may override this method to do some postprocessing for the action.
     * @param Action $action the action just executed.
     */
    protected function afterAction($action)
    {
    }
    
    /**
     * @return \benben\web\WebModule the module that this controller belongs to. It returns null
     * if the controller does not belong to any module
     */
    public function getModule()
    {
    	return $this->_module;
    }
    
 	public function getView()
    {
        if (null===$this->_view)
        {
            $this->_view = Benben::app()->getComponent('view');
            $this->_view->owner = $this;
        }
        return $this->_view;
    }
    
    public function setView(View $view)
    {
        $this->view = $view;
    }

    /**
     * @return the $layout
     */
    public function getLayout ()
    {
        return $this->_layout;
    }

    /**
     * @param string $layout            
     */
    public function setLayout ($layout)
    {
        $this->_layout = $layout;
    }
    
    public function getViewFile($viewName)
    {
    	if(empty($viewName))
    	{
    		$viewName = $this->_action->id;
    	}
    	return $this->_module->basePath.'/views/'.$this->_id.'/'.$viewName.'.php';
    }
    
    public function render($data = array(), $return = false, $viewName = '')
    {
    	$viewFile = $this->getViewFile($viewName);
    	$this->getView()->setData($data);
    	$output = $this->getView($this)->render($viewFile);
    	
    	if($return)
    	{
    		return $output;
    	}
    	else
    	{ 
    		echo $output;
    	}
    }
    
    public function renderPartial($data = array(), $return = false, $viewName = '')
    {
    	$viewFile = $this->getViewFile($viewName);
    	$this->getView()->setData($data);
    	$output = $this->getView($this)->renderPartial($viewFile);
    	
    	if($return)
    	{
    		return $output;
    	}
    	else
    	{
    		echo $output;
    	}
    }

    /**
     * Records a method call when an output cache is in effect.
     * When the content is served from the output cache, the recorded
     * method will be re-invoked.
     * @param string $context a property name of the controller. It refers to an object
     * whose method is being called. If empty it means the controller itself.
     * @param string $method the method name
     * @param array $params parameters passed to the method
     * @see benben\web\widgets\OutputCache
     */
    public function recordCachingAction($context,$method,$params)
    {
    	if($this->_cachingStack) // record only when there is an active output cache
    	{
    		foreach($this->_cachingStack as $cache)
    			$cache->recordAction($context,$method,$params);
    	}
    }
    
    /**
     * @param boolean $createIfNull whether to create a stack if it does not exist yet. Defaults to true.
     * @return CStack stack of {@link COutputCache} objects
     */
    public function getCachingStack($createIfNull=true)
    {
    	if(!$this->_cachingStack)
    		$this->_cachingStack=new CStack();
    	return $this->_cachingStack;
    }
}