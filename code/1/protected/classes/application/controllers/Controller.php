<?php
namespace application\controllers;

class Controller extends \benben\web\Controller
{
    public function filters()
    {
        return array(
            array('application\\components\\AuthFilter')
        );
    }
}