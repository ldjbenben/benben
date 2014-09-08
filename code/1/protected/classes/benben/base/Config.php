<?php

namespace benben\base;

/**
 * 
 * @author benben
 *
 * @property string $defaultController
 * @property string $defaultAction
 * @property array $modules
 * @property string $namespace
 */
class Config
{
	/**
	 * @var string 配置文件
	 */
	private $configFile;
	
	/**
	 * @var array
	 */
	private $configData;
	
	public function __construct($config = null)
	{
		if (!is_file($config)) 
		{
			throw new Exception(Exception::CONFIG_FILE_NOT_EXSITS);
		}
		
		$this->configFile = $config;
		$this->configData = include_once $this->configFile;
		
	}
	
	public function getDefaultController()
	{
	    if (isset($this->configData['defaultController']))
	    {
	        return $this->configData['defaultController'];
	    }
		return 'default';
	}
	
	public function getDefaultAction()
	{
		if (isset($this->configData['defaultAction']))
		{
			return $this->configData['defaultAction'];;
		}
		return 'index';
	}
	
	public function getModules()
	{
	    if (isset($this->configData['modules']))
	    {
	    	return $this->configData['modules'];
	    }
	    return array();
	}
	
	public function get($key)
	{
	    $ret = null;
	    if (isset($this->configData[$key]))
	    {
	    	$ret = $this->configData[$key];;
	    }
	    else
	    {
	        switch ($key)
	        {
	            case 'defaultController':
	                $ret = 'default';
	                break;
                case 'defaultAction':
                	$ret = 'index';
                	break;
	        }
	    }
	    
	    return $ret;
	}

	/**
	 * 获取应用的命名空间
	 */
	public function getNamespace()
	{
		 if (isset($this->configData['namespace']))
	    {
	    	return $this->configData['namespace'];
	    }
	    return '';
	}
	
}