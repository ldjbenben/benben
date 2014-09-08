<?php

namespace benben\utils;

class ClassHelper 
{
	/**
	 * Gets class name of the $model, but it not include namespace.
	 * @param Model $model
	 * @return string
	 */
	public static function getModelLastName($model)
	{
		$class_name = get_class($model);
		return ((($pos = strrpos($class_name, '\\'))!==false) ? substr($class_name, $pos+1) : $class_name);
	}
}