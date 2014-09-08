<?php

namespace benben\route;

use benben\web\HttpRequest;

require_once 'IUrlAnalyzer.php';

/** 
 * @author benben
 * 
 */
class UrlAnalyzer implements IUrlAnalyzer 
{
	private $_rules = array(
		'/^[^\/]{1,}[\/]?$/',
		'/^[^\/]{1,}\/[^\/]{1,}[\/]?$/',
		'/^[^\/]{1,}\/[^\/]{1,}\/[^\/]{1,}$/',
		'/^[^\/]{1,}\/[^\/]{1,}\/[^\/]{1,}\/(.*)?/',
	);
	
	public function init()
	{
	    
	}
	
	/* (non-PHPdoc)
	 * @see B_IUrlAnalyzer::parseUrl()
	 */
	public function parseUrl(HttpRequest $request) 
	{
		$path_info = $request->pathInfo ? substr($request->pathInfo, 1) : '';
		$matches = array();
		foreach ($this->_rules as $rule)
		{
				$match_count = preg_match_all($rule, $path_info, $matches);
				
				if ($match_count)
				{
					return $matches[0][0];
				}
		}
		
		return '';
	}
	
	/* (non-PHPdoc)
	 * @see B_IUrlAnalyzer::parseParams()
	*/
	public function parseParams($params_path)
	{
		$params = array();
		
		if(!empty($params_path))
		{
			$path = trim($params_path);
			$arr = explode('/', $path);
			$path = $arr[0];
			$arr = explode('-', $path);
			$count = count($arr);
			
			for($i=0; $i<$count; $i++)
			{
				$params[$arr[$i]] = isset($arr[$i+1]) ? $arr[$i+1] : '';
				$i++; 
			}
		}
		
		return $params;
	}

	/* (non-PHPdoc)
	 * @see B_IUrlAnalyzer::createUrl()
	 */
	public function createUrl($path, array $params = null)
	{
		$path = trim($path, '/');
		
		if ($params) 
		{
			$path .= '/';
			$space = '';
			foreach ($params as $k=>$v)
			{
				$path .= $space.$k.'-'.$v;
				$space = '-';
			}
		}
		
		return $path;
	}

	
}