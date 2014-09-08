<?php
namespace benben\validators;

use benben\base\Component;
use benben\Benben;
/**
 * Validator class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Validator is the base class for all validators.
 *
 * Child classes must implement the {@link validateAttribute} method.
 *
 * The following properties are defined in Validator:
 * <ul>
 * <li>{@link attributes}: array, list of attributes to be validated;</li>
 * <li>{@link message}: string, the customized error message. The message
 *   may contain placeholders that will be replaced with the actual content.
 *   For example, the "{attribute}" placeholder will be replaced with the label
 *   of the problematic attribute. Different validators may define additional
 *   placeholders.</li>
 * <li>{@link on}: string, in which scenario should the validator be in effect.
 *   This is used to match the 'on' parameter supplied when calling {@link Model::validate}.</li>
 * </ul>
 *
 * When using {@link reateValidator} to create a validator, the following aliases
 * are recognized as the corresponding built-in validator classes:
 * <ul>
 * <li>required: {@link RequiredValidator}</li>
 * <li>filter: {@link FilterValidator}</li>
 * <li>match: {@link RegularExpressionValidator}</li>
 * <li>email: {@link EmailValidator}</li>
 * <li>url: {@link UrlValidator}</li>
 * <li>unique: {@link UniqueValidator}</li>
 * <li>compare: {@link CompareValidator}</li>
 * <li>length: {@link StringValidator}</li>
 * <li>in: {@link RangeValidator}</li>
 * <li>numerical: {@link NumberValidator}</li>
 * <li>captcha: {@link CaptchaValidator}</li>
 * <li>type: {@link TypeValidator}</li>
 * <li>file: {@link FileValidator}</li>
 * <li>default: {@link DefaultValueValidator}</li>
 * <li>exist: {@link ExistValidator}</li>
 * <li>boolean: {@link BooleanValidator}</li>
 * <li>date: {@link DateValidator}</li>
 * <li>safe: {@link SafeValidator}</li>
 * <li>unsafe: {@link UnsafeValidator}</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package system.validators
 * @since 1.0
 */
abstract class Validator extends Component
{
	/**
	 * @var array list of built-in validators (name=>class)
	 */
	public static $builtInValidators=array(
		'required'=>'RequiredValidator',
		'filter'=>'FilterValidator',
		'match'=>'RegularExpressionValidator',
		'email'=>'EmailValidator',
		'url'=>'UrlValidator',
		'unique'=>'UniqueValidator',
		'compare'=>'CompareValidator',
		'length'=>'StringValidator',
		'in'=>'RangeValidator',
		'numerical'=>'NumberValidator',
		'captcha'=>'CaptchaValidator',
		'type'=>'TypeValidator',
		'file'=>'FileValidator',
		'default'=>'DefaultValueValidator',
		'exist'=>'ExistValidator',
		'boolean'=>'BooleanValidator',
		'safe'=>'SafeValidator',
		'unsafe'=>'UnsafeValidator',
		'date'=>'DateValidator',
	);

	/**
	 * @var array list of attributes to be validated.
	 */
	public $attributes;
	/**
	 * @var string the user-defined error message. Different validators may define various
	 * placeholders in the message that are to be replaced with actual values. All validators
	 * recognize "{attribute}" placeholder, which will be replaced with the label of the attribute.
	 */
	public $message;
	/**
	 * @var boolean whether this validation rule should be skipped when there is already a validation
	 * error for the current attribute. Defaults to false.
	 * @since 1.1.1
	 */
	public $skipOnError=false;
	/**
	 * @var array list of scenarios that the validator should be applied.
	 * Each array value refers to a scenario name with the same name as its array key.
	 */
	public $on;
	/**
	 * @var array list of scenarios that the validator should not be applied to.
	 * Each array value refers to a scenario name with the same name as its array key.
	 * @since 1.1.11
	 */
	public $off;
	/**
	 * @var boolean whether attributes listed with this validator should be considered safe for massive assignment.
	 * Defaults to true.
	 * @since 1.1.4
	 */
	public $safe=true;
	/**
	 * @var boolean whether to perform client-side validation. Defaults to true.
	 * Please refer to {@link ActiveForm::enableClientValidation} for more details about client-side validation.
	 * @since 1.1.7
	 */
	public $enableClientValidation=true;

	/**
	 * Validates a single attribute.
	 * This method should be overridden by child classes.
	 * @param Model $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 */
	abstract protected function validateAttribute($object,$attribute);


	/**
	 * Creates a validator object.
	 * @param string $name the name or class of the validator
	 * @param Model $object the data object being validated that may contain the inline validation method
	 * @param mixed $attributes list of attributes to be validated. This can be either an array of
	 * the attribute names or a string of comma-separated attribute names.
	 * @param array $params initial values to be applied to the validator properties
	 * @return Validator the validator
	 */
	public static function createValidator($name,$object,$attributes,$params=array())
	{
		if(is_string($attributes))
			$attributes=preg_split('/[\s,]+/',$attributes,-1,PREG_SPLIT_NO_EMPTY);

		if(isset($params['on']))
		{
			if(is_array($params['on']))
				$on=$params['on'];
			else
				$on=preg_split('/[\s,]+/',$params['on'],-1,PREG_SPLIT_NO_EMPTY);
		}
		else
			$on=array();

		if(isset($params['off']))
		{
			if(is_array($params['off']))
				$off=$params['off'];
			else
				$off=preg_split('/[\s,]+/',$params['off'],-1,PREG_SPLIT_NO_EMPTY);
		}
		else
			$off=array();

		if(method_exists($object,$name))
		{
			$validator=new InlineValidator();
			$validator->attributes=$attributes;
			$validator->method=$name;
			if(isset($params['clientValidate']))
			{
				$validator->clientValidate=$params['clientValidate'];
				unset($params['clientValidate']);
			}
			$validator->params=$params;
			if(isset($params['skipOnError']))
				$validator->skipOnError=$params['skipOnError'];
		}
		else
		{
			$params['attributes']=$attributes;
			if(isset(self::$builtInValidators[$name]))
				$className=Benben::import('benben\\validators\\'.self::$builtInValidators[$name],true);
			else
				$className=Benben::import($name,true);
			$validator=new $className;
			foreach($params as $name=>$value)
				$validator->$name=$value;
		}

		$validator->on=empty($on) ? array() : array_combine($on,$on);
		$validator->off=empty($off) ? array() : array_combine($off,$off);

		return $validator;
	}

	/**
	 * Validates the specified object.
	 * @param Model $object the data object being validated
	 * @param array $attributes the list of attributes to be validated. Defaults to null,
	 * meaning every attribute listed in {@link attributes} will be validated.
	 */
	public function validate($object,$attributes=null)
	{
		if(is_array($attributes))
			$attributes=array_intersect($this->attributes,$attributes);
		else
			$attributes=$this->attributes;
		
		foreach($attributes as $attribute)
		{
			if(!$this->skipOnError || !$object->hasErrors($attribute))
				$this->validateAttribute($object,$attribute);
		}
	}

	/**
	 * Returns the JavaScript needed for performing client-side validation.
	 * Do not override this method if the validator does not support client-side validation.
	 * Two predefined JavaScript variables can be used:
	 * <ul>
	 * <li>value: the value to be validated</li>
	 * <li>messages: an array used to hold the validation error messages for the value</li>
	 * </ul>
	 * @param Model $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 * @return string the client-side validation script. Null if the validator does not support client-side validation.
	 * @see CActiveForm::enableClientValidation
	 * @since 1.1.7
	 */
	public function clientValidateAttribute($object,$attribute)
	{
	}

	/**
	 * Returns a value indicating whether the validator applies to the specified scenario.
	 * A validator applies to a scenario as long as any of the following conditions is met:
	 * <ul>
	 * <li>the validator's "on" property is empty</li>
	 * <li>the validator's "on" property contains the specified scenario</li>
	 * </ul>
	 * @param string $scenario scenario name
	 * @return boolean whether the validator applies to the specified scenario.
	 */
	public function applyTo($scenario)
	{
		if(isset($this->off[$scenario]))
			return false;
		return empty($this->on) || isset($this->on[$scenario]);
	}

	/**
	 * Adds an error about the specified attribute to the active record.
	 * This is a helper method that performs message selection and internationalization.
	 * @param Model $object the data object being validated
	 * @param string $attribute the attribute being validated
	 * @param string $message the error message
	 * @param array $params values for the placeholders in the error message
	 */
	protected function addError($object,$attribute,$message,$params=array())
	{
		$params['{attribute}']=$object->getAttributeLabel($attribute);
		$object->addError($attribute,strtr($message,$params));
	}

	/**
	 * Checks if the given value is empty.
	 * A value is considered empty if it is null, an empty array, or the trimmed result is an empty string.
	 * Note that this method is different from PHP empty(). It will return false when the value is 0.
	 * @param mixed $value the value to be checked
	 * @param boolean $trim whether to perform trimming before checking if the string is empty. Defaults to false.
	 * @return boolean whether the value is empty
	 */
	protected function isEmpty($value,$trim=false)
	{
		return $value===null || $value===array() || $value==='' || $trim && is_scalar($value) && trim($value)==='';
	}
}

