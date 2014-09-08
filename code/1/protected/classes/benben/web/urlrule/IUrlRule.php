<?php
namespace benben\web\urlrule;

interface IUrlRule
{
    function parseUrl($pathInfo);
    function createUrl($controller, $action='', $params=array());
}