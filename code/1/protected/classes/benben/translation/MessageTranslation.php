<?php

namespace benben\translation;

use benben\Benben;

require_once ('ITranslation.php');


/**
 * @author benben
 * @version 1.0
 * @created 30-May-2013 4:50:23 PM
 */
class MessageTranslation implements ITranslation
{
    protected $data = array(); 
    
	/**
	 * 文本消息翻译
	 * @param category 类别
	 * @param message 消息， 消息中可以包含一些动态参数，参数的取值由params参数传递
	 * @param params 消息参数
	 */
	public function translate($category, $message, array $params = array())
	{
	    // 获取当前所在地区
	    $locale = Benben::app()->locale;
	    // 获取当前模块
	    $module = Benben::app()->module;
	    $language_file = $module->basePath.'/languages/'.$locale.'.php';
	    
	    if (file_exists($language_file))
	    {
	        $this->data = include_once $language_file;
	    }
	    
	    if (isset($this->data[$category]) && isset($this->data[$category][$message]))
	    {
	        return $this->data[$category][$message];
	    }
	    
	    return $message;
	}

}
