<?php

namespace benben\web\form;

use benben\base\Component;

/**
 * @author benben
 * @property string $name
 * @property string $value
 */
abstract class FormElement extends Component
{
    protected $attributes;
    
    protected function init()
    {
        $this->attributes = array(
                'name'=>'',
                'value'=>'',
        );
    }
    
    /**
     * 返回表单字段HTML
     * @return string
     */
    abstract public function toString();
    
    /**
     * 为字段增加属性
     * @param string $name
     * @param string $value
     */
    public function addAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    
    /**
     * 为字段增加属性
     * @param array $attributes
     */
    public function addAttributes($attributes)
    {
    	if ($attributes)
    	{
    		$this->attributes = array_merge($this->attributes, $attributes);
    	}
    }
    
    protected function formatAttribute()
    {
        $str = '';
        foreach ($this->attributes as $name=>$value)
        {
            if(!empty($value))
            {
                $str .= " {$name}=\"{$value}\"";
            }
        }
        return substr($str,1);
    }
    
    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }
    
    public function getName()
    {
       return $this->attributes['name'];
    }
    
    public function setValue($value)
    {
        $this->attributes['value'] = $value;
    }
    
    public function getValue()
    {
        return $this->attributes['value'];
    }
    
}