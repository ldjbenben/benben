<?php
namespace benben\db\ar;
use benben\base\Component;
use benben\base\Exception;

abstract class Model extends Component
{
    private $_errors=array();	// attribute name => array of errors
    private $_validators;  		// validators
    
    /**
     * Returns the list of attribute names of the model.
     * @return array list of attribute names.
     */
    abstract public function attributeNames();
    
    /**
     * Returns the validation rules for attributes.
     *
     * This method should be overridden to declare validation rules.
     * Each rule is an array with the following structure:
     * <pre>
     * array('attribute list', 'validator name', 'on'=>'scenario name', ...validation parameters...)
     * </pre>
     * where
     * <ul>
     * <li>attribute list: specifies the attributes (separated by commas) to be validated;</li>
     * <li>validator name: specifies the validator to be used. It can be the name of a model class
     *   method, the name of a built-in validator, or a validator class (or its path alias).
     *   A validation method must have the following signature:
     * <pre>
     * // $params refers to validation parameters given in the rule
     * function validatorName($attribute,$params)
     * </pre>
     *   A built-in validator refers to one of the validators declared in {@link CValidator::builtInValidators}.
     *   And a validator class is a class extending {@link CValidator}.</li>
     * <li>on: this specifies the scenarios when the validation rule should be performed.
     *   Separate different scenarios with commas. If this option is not set, the rule
     *   will be applied in any scenario. Please see {@link scenario} for more details about this option.</li>
     * <li>additional parameters are used to initialize the corresponding validator properties.
     *   Please refer to individal validator class API for possible properties.</li>
     * </ul>
     *
     * The following are some examples:
     * <pre>
     * array(
     *     array('username', 'required'),
     *     array('username', 'length', 'min'=>3, 'max'=>12),
     *     array('password', 'compare', 'compareAttribute'=>'password2', 'on'=>'register'),
     *     array('password', 'authenticate', 'on'=>'login'),
     * );
     * </pre>
     *
     * Note, in order to inherit rules defined in the parent class, a child class needs to
     * merge the parent rules with child rules using functions like array_merge().
     *
     * @return array validation rules to be applied when {@link validate()} is called.
     * @see scenario
     */
    public function rules()
    {
    	return array();
    }
    
    /**
     * Returns the attribute labels.
     * Attribute labels are mainly used in error messages of validation.
     * By default an attribute label is generated using {@link generateAttributeLabel}.
     * This method allows you to explicitly specify attribute labels.
     *
     * Note, in order to inherit labels defined in the parent class, a child class needs to
     * merge the parent labels with child labels using functions like array_merge().
     *
     * @return array attribute labels (name=>label)
     * @see generateAttributeLabel
     */
    public function attributeLabels()
    {
    	return array();
    }
    
    /**
     * Performs the validation.
     *
     * This method executes the validation rules as declared in {@link rules}.
     * Only the rules applicable to the current {@link scenario} will be executed.
     * A rule is considered applicable to a scenario if its 'on' option is not set
     * or contains the scenario.
     *
     * Errors found during the validation can be retrieved via {@link getErrors}.
     *
     * @param array $attributes list of attributes that should be validated. Defaults to null,
     * meaning any attribute listed in the applicable validation rules should be
     * validated. If this parameter is given as a list of attributes, only
     * the listed attributes will be validated.
     * @param boolean $clearErrors whether to call {@link clearErrors} before performing validation
     * @return boolean whether the validation is successful without any error.
     * @see beforeValidate
     * @see afterValidate
     */
    public function validate($attributes=null, $clearErrors=true)
    {
    	if($clearErrors)
    		$this->clearErrors();
    	if($this->beforeValidate())
    	{
    		foreach($this->getValidators() as $validator)
    			$validator->validate($this,$attributes);
    		$this->afterValidate();
    		return !$this->hasErrors();
    	}
    	else
    		return false;
    }
    
    /**
     * Returns all the validators declared in the model.
     * This method differs from {@link getValidators} in that the latter
     * would only return the validators applicable to the current {@link scenario}.
     * Also, since this method return a {@link CList} object, you may
     * manipulate it by inserting or removing validators (useful in behaviors).
     * For example, <code>$model->validatorList->add($newValidator)</code>.
     * The change made to the {@link CList} object will persist and reflect
     * in the result of the next call of {@link getValidators}.
     * @return CList all the validators declared in the model.
     * @since 1.1.2
     */
    public function getValidatorList()
    {
    	if($this->_validators===null)
    		$this->_validators=$this->createValidators();
    	return $this->_validators;
    }
    
    /**
     * Returns the validators applicable to the current {@link scenario}.
     * @param string $attribute the name of the attribute whose validators should be returned.
     * If this is null, the validators for ALL attributes in the model will be returned.
     * @return array the validators applicable to the current {@link scenario}.
     */
    public function getValidators($attribute=null)
    {
    	if($this->_validators===null)
    		$this->_validators=$this->createValidators();
    
    	$validators=array();
    	$scenario=$this->getScenario();
    	foreach($this->_validators as $validator)
    	{
    		if($validator->applyTo($scenario))
    		{
    			if($attribute===null || in_array($attribute,$validator->attributes,true))
    				$validators[]=$validator;
    		}
    	}
    	return $validators;
    }
    
    /**
     * Creates validator objects based on the specification in {@link rules}.
     * This method is mainly used internally.
     * @return List validators built based on {@link rules()}.
     */
    public function createValidators()
    {
    	$validators=new CList;
    	foreach($this->rules() as $rule)
    	{
    		if(isset($rule[0],$rule[1]))  // attributes, validator name
    			$validators->add(CValidator::createValidator($rule[1],$this,$rule[0],array_slice($rule,2)));
    		else
    			throw new Exception(Yii::t('yii','{class} has an invalid validation rule. The rule must specify attributes to be validated and the validator name.',
    					array('{class}'=>get_class($this))));
    	}
    	return $validators;
    }
}