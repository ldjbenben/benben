<?php

namespace benben\web\form\validators;

use benben\web\form\Validator;

require_once dirname(__DIR__).'\Validator.php';

class TextValidator extends Validator
{
    /**
     * 字符最大长度限制
     */
    public $maxLen;
    /**
     * 是否可以为空
     * @var bool
     */
    public $empty;
    
    /*
     * (non-PHPdoc) @see B_IValidator::validate()
     */
    public function validate ($value)
    {
        $ret = true;
        $this->value = $value;
        if (strlen($value)>$this->maxLen)
        {
            $this->error = '字符格式不合法';
            $ret = false;
        }
        
        return $ret;
    }
    
}