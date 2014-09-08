<?php
namespace benben\web\urlrule;

use benben\web\urlrule\IUrlRule;

class BenbenRule implements IUrlRule
{
    private $_controllerTag='<controller>';
    private $_actionTag='<action>';
    private $_paramTag='<params>';
    private $_paramSeparator='-';
    
    private $_rule =  '<controller>\/?<action>?<params>?'; 
    
    public function __construct()
    {
        //$this->_rule = $rule;
    }
    
    public function createUrl($controller, $action='', $params=array())
    {
        $route = '';
        if (!empty($controller) && !empty($action))
        {
            $route = $controller.'/'.$action;
        }
        $parameters = '-';
        foreach ($params as $key=>$value)
        {
            $parameters .= '-'.$key.'-'.$value;
        }
        return '/'.$route.substr($parameters,1);
    }
    
    public function parseUrl($pathInfo)
    {
        $pathInfo = trim($pathInfo,'/');
        $search = array($this->_controllerTag, $this->_actionTag, $this->_paramTag);
        $replace = array('(\w*\/?\w*?)\/','(\w*)','(.*)');
        $pattern = '/^'.str_replace($search, $replace, $this->_rule).'$/u';
        $mathches = array();
        preg_match($pattern, $pathInfo, $mathches);
        if (!empty($mathches[3]) && strpos($mathches[3], $this->_paramSeparator)===0)
        {
        	return "{$mathches[1]}/{$mathches[2]}".str_replace($this->_paramSeparator, '/', $mathches[3]);
        }
        elseif(!empty($mathches))
        {
        	return "{$mathches[1]}/{$mathches[2]}";
        }
    }
    
    public function setRule($rule)
    {
        $this->_rule = $rule;
    }
}