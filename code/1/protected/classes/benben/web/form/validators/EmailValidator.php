<?php

namespace benben\web\form\validators;

use benben\web\form\Validator;

require_once dirname(__DIR__).'\Validator.php';

class EmailValidator extends Validator
{
    /*
     * (non-PHPdoc) @see B_IValidator::validate()
     */
    public function validate ($value)
    {
        $ret = true;
        $this->value = $value;
        $pattern = '/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/';
        $time = (int)preg_match($pattern, $this->value);
        if (0 == $time)
        {
            $this->error = '邮箱格式不合法';
            $ret = false;
        }
        
        return $ret;
    }
    
}