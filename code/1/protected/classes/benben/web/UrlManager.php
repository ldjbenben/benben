<?php
namespace benben\web;

use benben\Benben;

use benben\base\ApplicationComponent;

class UrlManager extends ApplicationComponent
{
    const GET_FORMAT='get';
    const PATH_FORMAT='path';
    
    public $caseSensitive=true;
    public $routeVar='r';
    /**
     * @var string the URL suffix used.
     * For example, ".html" can be used so that the URL looks like pointing to a static HTML page. Defaults to empty.
     */
    public $urlSuffix='';
    
    private $_rule = null;
    private $_rules=array();
    private $_urlFormat = self::GET_FORMAT;
    private $_baseUrl;
    
    public function init()
    {
        $this->_isInitialized = true;
    }
    
    public function setRule($rule)
    {
        Benben::import($rule);
        $this->_rule = new $rule();
    }
    
    /**
     * Parses the user request.
     * @param HttpRequest $request the request application component
     * @return string the route (controllerID/actionID) and perhaps GET parameters in path format.
     */
    public function parseUrl($request)
    {
        if (self::PATH_FORMAT===$this->getUrlFormat())
        {
            $rawPathInfo=$request->getPathInfo();
            $pathInfo=$this->removeUrlSuffix($rawPathInfo, $this->urlSuffix);
            if ($this->_rule != null)
            {
                return $this->_rule->parseUrl($pathInfo);
            }
            return $pathInfo;
        }
        else if(isset($_GET[$this->routeVar]))
        	return $_GET[$this->routeVar];
        else if(isset($_POST[$this->routeVar]))
        	return $_POST[$this->routeVar];
        else
        	return '';
    }
    
    public function getUrlFormat()
    {
        return $this->_urlFormat;
    }
    
    public function setUrlFormat($format)
    {
        $this->_urlFormat;
    }
    
    /**
     * Removes the URL suffix from path info
     * @param string $pathInfo path info part in the URL
     * @param string $urlSuffix the URL suffix be removed
     * @return string path info with URL suffix removed.
     */
    public function removeUrlSuffix($pathInfo, $urlSuffix)
    {
        if ($urlSuffix!=='' && $urlSuffix===substr($pathInfo, -strlen($urlSuffix)))
        {
            return substr($pathInfo,0,-strlen($urlSuffix));
        }
        else
        {
            return $pathInfo;
        }
    }
    
    /**
     * Parses a path info into URL segments and saves them to $_GET and $_REQUEST.
     * @param string $pathInfo
     */
    public function parsePathInfo($pathInfo)
    {
        if(''===$pathInfo)
        {
            return;
        }
        $segs=explode('/', $pathInfo.'/');
        $n=count($segs);
        for ($i=0;$i<$n-1;$i+=2)
        {
            $key=$segs[$i];
            if(''===$key) continue;
            $_REQUEST[$key]=$_GET[$key] = $segs[$i+1];
        }
    }

    /**
     * Constructs a URL.
     * @param string $route the controller and the action (e.g. article/read)
     * @param array $params list of GET parameters (name=>value). Both the name and value will be URL-encoded.
     * If the name is '#', the corresponding value will be treated as an anchor
     * and will be appended at the end of the URL.
     * @param string $ampersand the token separating name-value pairs in the URL. Defaults to '&'.
     * @return string the constructed URL
     */
    public function createUrl($route,$params=array(),$ampersand='&')
    {
    	unset($params[$this->routeVar]);
    	foreach($params as $i=>$param)
    	if($param===null)
    		$params[$i]='';
    
    	if(isset($params['#']))
    	{
    		$anchor='#'.$params['#'];
    		unset($params['#']);
    	}
    	else
    		$anchor='';
    	$route=trim($route,'/');
    	foreach($this->_rules as $i=>$rule)
    	{
    		if(is_array($rule))
    			$this->_rules[$i]=$rule=Benben::createComponent($rule);
    		if(($url=$rule->createUrl($this,$route,$params,$ampersand))!==false)
    		{
    			if($rule->hasHostInfo)
    				return $url==='' ? '/'.$anchor : $url.$anchor;
    			else
    				return $this->getBaseUrl().'/'.$url.$anchor;
    		}
    	}
    	return $this->createUrlDefault($route,$params,$ampersand).$anchor;
    }
    

    /**
     * Creates a URL based on default settings.
     * @param string $route the controller and the action (e.g. article/read)
     * @param array $params list of GET parameters
     * @param string $ampersand the token separating name-value pairs in the URL.
     * @return string the constructed URL
     */
    protected function createUrlDefault($route,$params,$ampersand)
    {
    	if($this->getUrlFormat()===self::PATH_FORMAT)
    	{
    		$url=rtrim($this->getBaseUrl().'/'.$route,'/');
    		if($this->appendParams)
    		{
    			$url=rtrim($url.'/'.$this->createPathInfo($params,'/','/'),'/');
    			return $route==='' ? $url : $url.$this->urlSuffix;
    		}
    		else
    		{
    			if($route!=='')
    				$url.=$this->urlSuffix;
    			$query=$this->createPathInfo($params,'=',$ampersand);
    			return $query==='' ? $url : $url.'?'.$query;
    		}
    	}
    	else
    	{
    		$url=$this->getBaseUrl();
    		if(!$this->showScriptName)
    			$url.='/';
    		if($route!=='')
    		{
    			$url.='?'.$this->routeVar.'='.$route;
    			if(($query=$this->createPathInfo($params,'=',$ampersand))!=='')
    				$url.=$ampersand.$query;
    		}
    		else if(($query=$this->createPathInfo($params,'=',$ampersand))!=='')
    			$url.='?'.$query;
    		return $url;
    	}
    }
    
    /**
     * Returns the base URL of the application.
     * @return string the base URL of the application (the part after host name and before query string).
     * If {@link showScriptName} is true, it will include the script name part.
     * Otherwise, it will not, and the ending slashes are stripped off.
     */
    public function getBaseUrl()
    {
    	if($this->_baseUrl!==null)
    		return $this->_baseUrl;
    	else
    	{
    		if($this->showScriptName)
    			$this->_baseUrl=Benben::app()->getRequest()->getScriptUrl();
    		else
    			$this->_baseUrl=Benben::app()->getRequest()->getBaseUrl();
    		return $this->_baseUrl;
    	}
    }
    
    /**
     * Sets the base URL of the application (the part after host name and before query string).
     * This method is provided in case the {@link baseUrl} cannot be determined automatically.
     * The ending slashes should be stripped off. And you are also responsible to remove the script name
     * if you set {@link showScriptName} to be false.
     * @param string $value the base URL of the application
     * @since 1.1.1
     */
    public function setBaseUrl($value)
    {
    	$this->_baseUrl=$value;
    }

    /**
     * Creates a path info based on the given parameters.
     * @param array $params list of GET parameters
     * @param string $equal the separator between name and value
     * @param string $ampersand the separator between name-value pairs
     * @param string $key this is used internally.
     * @return string the created path info
     */
    public function createPathInfo($params,$equal,$ampersand, $key=null)
    {
    	$pairs = array();
    	foreach($params as $k => $v)
    	{
    		if ($key!==null)
    			$k = $key.'['.$k.']';
    
    		if (is_array($v))
    			$pairs[]=$this->createPathInfo($v,$equal,$ampersand, $k);
    		else
    			$pairs[]=urlencode($k).$equal.urlencode($v);
    	}
    	return implode($ampersand,$pairs);
    }
}