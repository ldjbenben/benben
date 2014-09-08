<?php

namespace benben\web\form;

interface IValidator
{
    /**
     * 验证是否合法
     * @return bool
     */
    function validate($value);
    /**
     * 获取错误信息
     * @return string
     */
    function getError();
}