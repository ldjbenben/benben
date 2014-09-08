<?php

namespace benben\model;

use benben\Benben;

use benben\base\Component;

class Model extends Component
{
    protected $table;
    protected $data;
    protected $attributes;
    protected $errors;
    /**
     * @var Database
     */
    protected $db;
    static protected $instance = null;
    
    /**
     * 子类应该覆盖此方法
     * @return B_Model
     */
    static public function model()
    {
        if (!(self::$instance instanceof self))
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    protected function init()
    {
        $this->db = Benben::app()->db;
        $this->attributes = $this->attributes();
        parent::init();
    }
    
    public function __set($key, $value)
    {
        if (isset($this->attributes[$key]))
        {
            $this->data[$key] = $value;
        }
    }
    
    public function __get($key)
	{
		if (isset($this->attributes[$key]))
		{
		    return isset($this->data[$key]) ? $this->data[$key] : '';
		}
		return null;
	}
    
    public function validate($attributes = null)
    {
        $result = true;
        if (empty($attributes))
        {
            $attributes = $this->data;
        }
        
        $rules = $this->rules();
        if (!empty($rules))
        {
            foreach ($rules as $validator_name=>$fields)
            {
                $validator_class_name = ucfirst($validator_name).'Validator';
                if (!class_exists($validator_class_name))
                {
                    $validator_class_name = 'B_'.ucfirst($validator_name).'Validator';
                }
                if (class_exists($validator_class_name))
                {
                    $validator = new $validator_class_name();
                    $fields = explode(',', $fields);
                    if (!empty($fields))
                    {
                        foreach ($fields as $field)
                        {
                            if(!$validator->validate($attributes[$field]))
                            {
                                $this->setError($field, $validator->error);
                                $result = false;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
    
    public function setError($attribute, $error)
    {
        $this->errors[$attribute] = $error;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    protected function attributes()
    {
    }
    
    public function label($attribute)
    {
        if (isset($this->attributes[$attribute]))
        {
            return $this->attributes[$attribute];
        }
        return null;
    }
    
    /**
     * 返回模型字段的验证规则
     * @return array
     */
    public function rules()
    {
        return array();
    }
    
    public function fetchAll()
    {
        $db_setting = Benben::app()->config->db;
        $sql = "SELECT * FROM `{$db_setting['tablePre']}{$this->table}`";
        return $this->db->queryAll($sql);
    }
    
}