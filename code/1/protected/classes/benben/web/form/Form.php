<?php

namespace benben\web\form;

use benben\web\form\elements\SelectElement;

use benben\web\form\elements\TextElement;

use benben\module\FormException;

use benben\web\widget\Widget;
use benben\web\helper\Html;

require_once 'elements\TextElement.php';
require_once 'elements\SelectElement.php';
require_once BENBEN_PATH.'\web\widget\Widget.php';
require_once BENBEN_PATH.'\web\helpers\Html.php';

/**
 * 
 * @author benben
 * @property string $action
 * @property string $decorator
 * @property string $method
 */
class Form extends Widget
{
    /*
     * (non-PHPdoc) @see B_IWidget::init()
     */
    public function initialize ()
    {
        echo Html::tag('form', $this->attributes, false);
//         echo '<form id="'.$this->id.'" action="'.$this->action.'" method="'.$this->method.'">';
    }
    
    /*
     * (non-PHPdoc) @see B_IWidget::run()
     */
    public function run ()
    {
        echo '</form>';
    }
    
	public function label($name)
	{
	    return $this->model->label($name);
	}
	
	public function field($type, $name, $value, $params = null)
	{
	    $method_name = "{$type}Field";
	    if (method_exists($this, $method_name))
	    {
	        $jsonValue = $this->isJson($params);
	        if ($jsonValue)
	        {
	            $params = $jsonValue;
	        }
	        return call_user_func(array($this, $method_name), $name, $value, $params);
	    }
	    throw new FormException('field class not exsits!');
	}
	
	/**
	 * 判断是否为json数据
	 * @param string $json
	 * @return false|mixed
	 */
	protected function isJson($json)
	{
	    $data = json_decode($json);
	    if (NULL == $data)
	    {
	        return false;
	    }
	    return $data;
	}
	
	/**
	 * 普通文本字段
	 * @param string $name 表单字段名称
	 * @param string $value 表单字段值
	 * @param array $params
	 *     可接受的参数：
	 *     array(
	 *         'attributes'=>array() 字段属性
	 *     )
	 * @return string
	 */
	public function textField($name, $value, $params = null)
    {
        $attributes = array();
        
        if (!empty($params) && is_array($params))
        {
            if (isset($params))
            {
                $attributes = $params['attributes'];
            }
        }
        
        $field = new TextElement();
        $field->name = $name;
        $field->value = $value;
        $field->addAttributes($attributes);
        
        /* if ($this->decorator)
        {
            $class_name = ucfirst($this->decorator).'Decorator';
            if (!class_exists($class_name))
            {
                $class_name = 'B_'.ucfirst($this->decorator).'Decorator';
            }
            if (class_exists($class_name))
            {
                $decorator = new $class_name($field);
                return $decorator->toString($field);
            }
        }
         */
        return $field->toString();
    }
    
    /**
     * 表单提交按钮
     * @return string
     */
    public function submit($name = '', $value = '', $attributes = null)
    {
        if (!empty($name))
        {
            $attributes['name'] = $name;
        }
        if(!empty($value))
        {
            $attributes['value'] = $value;
        }
        $attributes['type'] = 'submit';
        return Html::tag('input',$attributes);
    }
    
    /**
     * 下拉选择框
     * @param string $name
     * @param array $params
     *     可接受的参数：
	 *     array(
	 *         'attributes'=>array(), 字段属性
	 *         'options'=>array() 选择项
	 *     )
     * @return string
     */
    public function selectField($name, $value, $params)
    {
        $attributes = array();
        $options = array();
        
        if (!empty($params) && is_array($params))
        {
        	if (isset($params['attributes']))
        	{
        		$attributes = $params['attributes'];
        	}
        	
        	if(isset($params['options']))
        	{
        	    $options = $params['options'];
        	}
        }
        
    	$field = new SelectElement();
    	$field->name = $name;
    	$field->value = $value;
    	$field->options = $options;
    	$field->addAttributes($attributes);
    	
    	return $field->toString();
    }
    
}