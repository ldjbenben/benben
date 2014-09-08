<?php
namespace benben\base;

use benben\Benben;

class HttpException extends Exception
{
    public function __construct($code, $message)
    {
        parent::__construct($message);        
    }
}