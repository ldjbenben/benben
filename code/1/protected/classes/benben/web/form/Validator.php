<?php

namespace benben\web\form;

use benben\base\Component;

require_once 'IValidator.php';

abstract class Validator extends Component implements IValidator
{
    /**
     * 被验证的数据
     * @var mixed
     */
    protected $value;
    /**
     * 错误信息
     * @var string
     */
    protected $error;
    
    /*
     * (non-PHPdoc) @see B_IValidator::getError()
    */
    public function getError ()
    {
    	return $this->error;
    }
}