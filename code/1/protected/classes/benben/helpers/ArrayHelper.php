<?php

namespace benben\helpers;

class ArrayHelper
{
    static public function makeMap($data, $key, $value)
    {
        $map = array();
        if (is_array($data) && !empty($data))
        {
            foreach ($data as $k=>$v)
            {
                $map[$v[$key]] = $v[$value];
            }
        }
        return $map;
    }
}