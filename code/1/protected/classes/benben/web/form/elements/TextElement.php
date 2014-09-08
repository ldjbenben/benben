<?php

namespace benben\web\form\elements;

use benben\web\form\FormElement;

require_once dirname(__DIR__).'\FormElement.php';

class TextElement extends FormElement
{
    public function toString()
    {
        $str = '';
        foreach($this->attributes as $name=>$value)
        {
            $str = '<input type="text" '.$this->formatAttribute().' />';
        }
        return $str;
    }
}
