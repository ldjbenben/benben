<?php
namespace benben\base;

use benben\web\HttpRequest;
use benben\db\DbConnection;
use benben\Benben;

/**
 * 
 * @author benben
 *
 * @property \benben\cache\ICache $cache
 * @property \benben\web\HttpRequest $request
 * @property \benben\db\DbConnection $db
 */
abstract class Application extends Module
{
	/**
	 * @var string the charset currently used for the application. Defaults to 'UTF-8'.
	 */
	public $charset='UTF-8';
    /**
     * @var HttpRequest
     */
    protected $_request = null;
    /**
     * @var Module
     */
    protected $_module = null;
    /**
     * 数据库操作对象
     * @var DbConnection
     */
    protected $_db = null;
    /**
     * 当前地区,取值为地区的缩写如中国为zh_cn,英国为en
     * @var string
     */
    public $sourceLanguage = 'zh_cn';
    private $_language;
    private $_components = array();
    private $_componentConfig=array();
    private $_modules = array();
    private $_moduleConfig = array();
    
    public function __construct($config = null)
    {
        if(is_string($config))
        {
        	$config=require($config);
        }
        
        if (isset($config['basePath']))
        {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        }
        else
        {
            $this->setBasePath('protected');
        }
        
        Benben::setPathOfAlias('application', APPLICATION_PATH);
        $this->registerCoreComponents();
        $this->configure($config);
        Benben::setLogger($this->getComponent('log'));
        $this->init();
    }
    
    protected function init()
    {
    }
    
    protected function registerCoreComponents()
    {
        $components=array(
        		'coreMessages'=>array(
        				'class'=>'benben\\i18n\\PhpMessageSource',
        				'language'=>'en_us',
        				'basePath'=>BENBEN_PATH.DIRECTORY_SEPARATOR.'messages',
        		),
                'db'=>array(
                		'class'=>'benben\db\DbConnection',
                ),
        		'routeAnalyzer'=>array(
        				'class'=>'benben\route\UrlAnalyzer',
        		),
                'urlManager'=>array(
                        'class'=>'benben\web\UrlManager',
                 ),
        		'request'=>array(
        				'class'=>'benben\web\HttpRequest',
        		),
        		'view'=>array(
        				'class'=>'benben\web\view\BasicView',
        		),
                'log'=>array(
                		'class'=>'benben\log\Logger',
                ),
        );
        
        $this->setComponents($components);
    }
    
    public abstract function processRequest();
    
    public function run()
    {
        $this->processRequest();
        $this->afterRequest();
    }
    
    protected function afterRequest()
    {
    	Benben::getLogger()->flush();
    }
    
    /**
     * Puts a component under the management of the module.
     * The component will be initialized by clalling its {@link ApplicationComponent::init() init()}
     * method if it has not done so.
     * @param string $id component ID
     * @param IApplicationComponent $component the component to be added to the module.
     * If this parameter is null, it will unload the component from the module.
     */
    public function setComponent($id, $component)
    {
    	if(null === $component)
    	{
    		unset($this->_components[$id]);
    		unset($this->_componentConfig[$id]);
    	}
    	else
    	{
    		$this->_components[$id] = $component;
    		if(!$component->getIsInitialized())
    		{
    			$component->init();
    		}
    	}
    }
    
    /**
     * Sets the application components.
     * When a configuration is used to specify a component, it should consist of
     * the component's initial property values (name-value pairs). Additionally,
     * a component can be enabled (default) or disabled by specifying the 'enabled' value
     * in the configuration.
     *
     * If a configuration is specified with an ID that is the same as an existing
     * component or configuration, the existing one will be replaced silently.
     *
     * The following is the configuration for two components:
     * <pre>
     * array(
     *     'db'=>array(
     *         'class'=>'DbConnection',
     *         'connectionString'=>'sqlite:path/to/file.db',
     *     ),
     *     'cache'=>array(
     *         'class'=>'CDbCache',
     *         'connectionID'=>'db',
     *         'enabled'=>!BENBEN_DEBUG,    // enable caching in non-debug mode
     *     ),
     * );
     * </pre>
     * @param array $components application components(id=>component configuration or instances)
     * @param boolean $merge wheather to merge the new component configuration with the existing one.
     * Defaults to true, meaning the previously registered component configuration of the same ID
     * will be merged with the new configuration. If false, the existing configuration will be replaced completely.
     */
    public function setComponents($components, $merge=true)
    {
    	foreach ($components as $id=>$component)
    	{
    		if ($component instanceof IApplicationComponent)
    		{
    			$this->setComponent($id, $component);
    		}
    		elseif (isset($this->_componentConfig[$id]) && $merge)
    		{
    			$this->_componentConfig[$id] = array_merge($this->_componentConfig[$id],$component);
    			// $this->_componentConfig[$id]=CMap::mergeArray($this->_componentConfig[$id],$component);
    		}
    		else
    		{
    			$this->_componentConfig[$id] = $component;
    		}
    	}
    }
    
    /**
     * Retrieves the named application component.
     * @param string $id application component ID(case-sensitive)
     * @param boolean $createIfNull whether to create the component if it doesn't exist yet.
     * @return IApplicationComponent the application component instance, null if the application component is disabled or does not exist.
     */
    public function getComponent($id, $createIfNull=true)
    {
    	if(isset($this->_components[$id]))
    	{
    		return $this->_components[$id];
    	}
    	elseif (isset($this->_componentConfig[$id]) && $createIfNull)
    	{
    		$config = $this->_componentConfig[$id];
    		if (!isset($config['enabled']) || $config['enabled'])
    		{
    			Benben::trace("Loading \"{$id}\" application component", 'system.base.module');
    			unset($config['enabled']);
    			$component = Benben::createComponent($config);
    			$component->init();
    			return $this->_components[$id] = $component;
    		}
    	}
    }
    
    /**
     * Configures the sub-modules of this module.
     *
     * @param array $modules
     */
    public function setModules($modules)
    {
    	foreach ($modules as $id=>$module)
    	{
    		if (is_int($id))
    		{
    			$id = $module;
    			$module = array();
    		}
    		if(!isset($module['class']))
    		{
    			//Benben::setPathOfAlias($id, $this->getModulePath().DIRECTORY_SEPARATOR.$id);
    			$module['class'] = 'modules\\'.$id.'\\'.ucfirst($id).'Module';
    		}
    		if (isset($this->_moduleConfig[$id]))
    		{
    			$this->_moduleConfig[$id] = array_merge($this->_moduleConfig[$id], $module);
    		}
    		else
    		{
    			$this->_moduleConfig[$id] = $module;
    		}
    	}
    }
    
    /**
     * Retrieves the named application module.
     * The module has to be declared in {@link modules}. A new instance will be created
     * when calling this method with the given ID for the first name.
     * @param string $id application module ID (case-sensitive)
     * @return \benben\base\Module the module instance, null if the module is disabled or does not exist.
     */
    public function getModule($id)
    {
        if (isset($this->_modules[$id]))
        {
            return $this->_modules[$id];
        }
        elseif (isset($this->_moduleConfig[$id]))
        {
            $config=$this->_moduleConfig[$id];
            if(!isset($config['enabled']) || $config['enabled'])
            {
                 Benben::trace("Loading \"{$id}\ module", "system.base.Module");
                 $class=$config['class'];
                 unset($config['class'], $config['enabled']);
                 
                 if ($this === Benben::app())
                 {
                     $module=Benben::createComponent($class, $id, null, $config);
                 }
                 else
                 {
                     $module=Benben::createComponent($class, $this->getId().'/'.$id,$this,$config);
                 }
                 return $this->_modules[$id]=$module;
            }
        }
        return null;
    }
    
    /**
     * Returns the URL manager component.
     * @return UrlManager the URL manager component
     */
    public function getUrlManager()
    {
    	return $this->getComponent('urlManager');
    }

    /**
     * Returns the unique identifier for the application.
     * @return string the unique identifier for the application.
     */
    public function getId()
    {
    	if($this->_id!==null)
    		return $this->_id;
    	else
    		return $this->_id=sprintf('%x',crc32($this->getBasePath().$this->name));
    }
    
    /**
     * Sets the unique identifier for the application.
     * @param string $id the unique identifier for the application.
     */
    public function setId($id)
    {
    	$this->_id=$id;
    }
  
    /**
     * Returns the request component.
     * @return \benben\web\HttpRequest the request component
     */
    public function getRequest()
    {
    	return $this->getComponent('request');
    }
    
    /**
     * @return DbConnection
     */
    public function getDb()
    {
    	if (!($this->_db instanceof DbConnection))
    	{
    		$this->_db = $this->getComponent('db');
    	}
    	 
    	return $this->_db;
    }
    
    public function getLocale()
    {
    	return $this->locale;
    }
    
    public function getCache()
    {
        return $this->getComponent('cache');
    }
    
    public function getAuthManager()
    {
        return $this->getComponent('authManager');
    }
    
    public function createUrl($route, $params=array())
    {
    	return $this->getUrlManager()->createUrl($route, $params);
    }
    
    /**
     * Terminates the application.
     * This method replaces PHP's exit() function by calling
     * {@link onEndRequest} before exiting.
     * @param integer $status exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param boolean $exit whether to exit the current request. This parameter has been available since version 1.1.5.
     * It defaults to true, meaning the PHP's exit() function will be called at the end of this method.
     */
    public function end($status=0,$exit=true)
    {
    	if($this->getEventProxy()->hasEventHandler('onEndRequest'))
    		$this->onEndRequest(new Event($this));
    	if($exit)
    		exit($status);
    }
    
    /**
     * Returns the language that the user is using and the application should be targeted to.
     * @return string the language that the user is using and the application should be targeted to.
     * Defaults to the {@link sourceLanguage source language}.
     */
    public function getLanguage()
    {
    	return $this->_language===null ? $this->sourceLanguage : $this->_language;
    }
    
    /**
     * Specifies which language the application is targeted to.
     *
     * This is the language that the application displays to end users.
     * If set null, it uses the {@link sourceLanguage source language}.
     *
     * Unless your application needs to support multiple languages, you should always
     * set this language to null to maximize the application's performance.
     * @param string $language the user language (e.g. 'en_US', 'zh_CN').
     * If it is null, the {@link sourceLanguage} will be used.
     */
    public function setLanguage($language)
    {
    	$this->_language=$language;
    }
    
    /**
     * Returns the root path of the application.
     * @return string the root directory of the application. Defaults to 'protected'.
     */
    public function getBasePath()
    {
    	return $this->_basePath;
    }
}