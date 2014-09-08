<?php

use benben\web\form\Form;

require_once ('Form.php');

class ModelForm extends Form
{
    public function inputField(B_Model $model, $name, $params = null)
    {
        $value = $model->$name;
        return parent::inputField($name, $value, $params);
    }
    
    public function selectField(B_Model $model,$model, $name, $params)
    {
        $value = $model->$name;
        return parent::selectField($name, $value, $params);
    }
    
}