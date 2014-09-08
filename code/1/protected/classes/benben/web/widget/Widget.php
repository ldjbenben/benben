<?php

namespace benben\web\widget;

abstract class Widget
{
    protected $attributes;
    
    abstract public function initialize();
    abstract public function run();
    
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;    
    }
    
}