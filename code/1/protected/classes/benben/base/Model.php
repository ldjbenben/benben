<?php
namespace benben\base;

use benben\base\Component;
use benben\collections\CList;
use benben\validators\Validator;
use benben\Benben;
use benben\log\Logger;
use benben\validators\RequiredValidator;

/**
 * Model is the base class providing the common features needed by data model objects.
 *
 * Model defines the basic framework for data models that need to be validated.
 *
 * @property CList $validatorList All the validators declared in the model.
 * @property array $validators The validators applicable to the current {@link scenario}.
 * @property array $errors Errors for all attributes or the specified attribute. Empty array is returned if no error.
 * @property array $attributes Attribute values (name=>value).
 * @property string $scenario The scenario that this model is in.
 * @property array $safeAttributeNames Safe attribute names.
 */
class Model extends Component
{
   	private $_errors=array();	// attribute name => array of errors
	private $_validators;  		// validators
	private $_scenario='';  	// scenario
	
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
	 *   will be applied in any scenario that is not listed in "except". Please see {@link scenario} for more details about this option.</li>
	 * <li>except: this specifies the scenarios when the validation rule should not be performed.
	 *   Separate different scenarios with commas. Please see {@link scenario} for more details about this option.</li>
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
	
	protected function beforeValidate()
	{
		$event=new ModelEvent($this);
		$this->onBeforeValidate($event);
		return $event->isValid;
	}
	
	protected function afterValidate()
	{
		$this->onAfterValidate(new Event($this));
	}
	
	public function onAfterConstruct($event)
	{
		$this->getEventProxy()->raiseEvent('onAfterConstruct',$event);
	}
	
	public function onBeforeValidate($event)
	{
		$this->getEventProxy()->raiseEvent('onBeforeValidate',$event);
	}
	
	public function onAfterValidate($event)
	{
		$this->getEventProxy()->raiseEvent('onAfterValidate',$event);
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
	 * @return CList validators built based on {@link rules()}.
	 */
	public function createValidators()
	{
		$validators=new CList();
		foreach($this->rules() as $rule)
		{
			if(isset($rule[0],$rule[1]))  // attributes, validator name
				$validators->add(Validator::createValidator($rule[1],$this,$rule[0],array_slice($rule,2)));
			else
				throw new Exception(Benben::t('yii','{class} has an invalid validation rule. The rule must specify attributes to be validated and the validator name.',
						array('{class}'=>get_class($this))));
		}
		return $validators;
	}
	
	/**
	 * Returns a value indicating whether there is any validation error.
	 * @param string $attribute attribute name. Use null to check all attributes.
	 * @return boolean whether there is any error.
	 */
	public function hasErrors($attribute=null)
	{
		if($attribute===null)
			return $this->_errors!==array();
		else
			return isset($this->_errors[$attribute]);
	}
	
	/**
	 * Returns the errors for all attribute or a single attribute.
	 * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
	 * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
	 */
	public function getErrors($attribute=null)
	{
		if($attribute===null)
			return $this->_errors;
		else
			return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : array();
	}
	
	/**
	 * Returns the first error of the specified attribute.
	 * @param string $attribute attribute name.
	 * @return string the error message. Null is returned if no error.
	 */
	public function getError($attribute)
	{
		return isset($this->_errors[$attribute]) ? reset($this->_errors[$attribute]) : null;
	}
	
	/**
	 * Adds a new error to the specified attribute.
	 * @param string $attribute attribute name
	 * @param string $error new error message
	 */
	public function addError($attribute,$error)
	{
		$this->_errors[$attribute][]=$error;
	}
	
	/**
	 * Adds a list of errors.
	 * @param array $errors a list of errors. The array keys must be attribute names.
	 * The array values should be error messages. If an attribute has multiple errors,
	 * these errors must be given in terms of an array.
	 * You may use the result of {@link getErrors} as the value for this parameter.
	 */
	public function addErrors($errors)
	{
		foreach($errors as $attribute=>$error)
		{
			if(is_array($error))
			{
				foreach($error as $e)
					$this->addError($attribute, $e);
			}
			else
				$this->addError($attribute, $error);
		}
	}
	
	/**
	 * Removes errors for all attributes or a single attribute.
	 * @param string $attribute attribute name. Use null to remove errors for all attribute.
	 */
	public function clearErrors($attribute=null)
	{
		if($attribute===null)
			$this->_errors=array();
		else
			unset($this->_errors[$attribute]);
	}
	
	public function getScenario()
	{
		return $this->_scenario;
	}
	
	public function setScenario($value)
	{
		$this->_scenario=$value;
	}
	
	/**
	 * Returns the text label for the specified attribute.
	 * @param string $attribute the attribute name
	 * @return string the attribute label
	 * @see generateAttributeLabel
	 * @see attributeLabels
	 */
	public function getAttributeLabel($attribute)
	{
		$labels=$this->attributeLabels();
		if(isset($labels[$attribute]))
			return $labels[$attribute];
		else
			return $this->generateAttributeLabel($attribute);
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
	 * Generates a user friendly attribute label.
	 * This is done by replacing underscores or dashes with blanks and
	 * changing the first letter of each word to upper case.
	 * For example, 'department_name' or 'DepartmentName' becomes 'Department Name'.
	 * @param string $name the column name
	 * @return string the attribute label
	 */
	public function generateAttributeLabel($name)
	{
		return ucwords(trim(strtolower(str_replace(array('-','_','.'),' ',preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name)))));
	}
	
	/**
	 * Sets the attribute values in a massive way.
	 * @param array $values attribute values (name=>value) to be set.
	 * @param boolean $safeOnly whether the assignments should only be done to the safe attributes.
	 * A safe attribute is one that is associated with a validation rule in the current {@link scenario}.
	 * @see getSafeAttributeNames
	 * @see attributeNames
	 */
	public function setAttributes($values,$safeOnly=true)
	{
		if(!is_array($values))
			return;
		$attributes=array_flip($safeOnly ? $this->getSafeAttributeNames() : $this->attributeNames());
		foreach($values as $name=>$value)
		{
			if(isset($attributes[$name]))
			{
				$this->$name=$value;
			}
			else if($safeOnly)
				$this->onUnsafeAttribute($name,$value);
		}
	}
	
	/**
	 * Returns the attribute names that are safe to be massively assigned.
	 * A safe attribute is one that is associated with a validation rule in the current {@link scenario}.
	 * @return array safe attribute names
	 */
	public function getSafeAttributeNames()
	{
		$attributes=array();
		$unsafe=array();
		foreach($this->getValidators() as $validator)
		{
			if(!$validator->safe)
			{
				foreach($validator->attributes as $name)
					$unsafe[]=$name;
			}
			else
			{
				foreach($validator->attributes as $name)
					$attributes[$name]=true;
			}
		}
	
		foreach($unsafe as $name)
			unset($attributes[$name]);
		return array_keys($attributes);
	}
	
	/**
	 * Returns a value indicating whether the attribute is required.
	 * This is determined by checking if the attribute is associated with a
	 * {@link CRequiredValidator} validation rule in the current {@link scenario}.
	 * @param string $attribute attribute name
	 * @return boolean whether the attribute is required
	 */
	public function isAttributeRequired($attribute)
	{
		foreach($this->getValidators($attribute) as $validator)
		{
			if($validator instanceof RequiredValidator)
				return true;
		}
		return false;
	}
	
	/**
	 * This method is invoked when an unsafe attribute is being massively assigned.
	 * The default implementation will log a warning message if YII_DEBUG is on.
	 * It does nothing otherwise.
	 * @param string $name the unsafe attribute name
	 * @param mixed $value the attribute value
	 * @since 1.1.1
	 */
	public function onUnsafeAttribute($name,$value)
	{
		if(BENBEN_DEBUG)
			Benben::log(Benben::t('benben','Failed to set unsafe attribute "{attribute}" of "{class}".',array('{attribute}'=>$name, '{class}'=>get_class($this))),Logger::LEVEL_WARNING);
	}
}