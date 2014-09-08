<?php

namespace benben\web\form\elements;

use benben\web\form\FormElement;

require_once dirname(__DIR__).'\FormElement.php';

/**
 * @author benben
 * @property array $options
 */
class SelectElement extends FormElement
{
    protected $options;
    
    public function toString()
    {
        $str = '';
        foreach($this->attributes as $name=>$value)
        {
            $str = '<select '.$this->formatAttribute().'>';
            $str .= $this->formatOptions();
            $str .= '</select>';
        }
        return $str;
    }
    
    /**
     * 设置选择项
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }
    
    protected function formatOptions()
    {
        $str = '';
        if (is_array($this->options) && !empty($this->options))
        {
            foreach ($this->options as $value=>$text)
            {
                $select = '';
                if ($this->value == $value)
                {
                    $select = ' selected="true"';
                }
                $str .= "<option value=\"{$value}\"{$select}>{$text}</option>";
            }
        }
        return $str;
    }
    
}
