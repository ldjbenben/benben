<?php

namespace benben\web\widget;

class WidgetFactory
{
    /**
     * @param string $className
     * @param array $params
     * @return B_Widget
     */
    public function createWidget($className, $params = null)
    {
        $widget = new $className();
        
        if (is_array($params) && !empty($params))
        {
            foreach ($params as $property=>$value)
            {
                $widget->$property = $value;
            }
        }
        
        return $widget;
    }
}