<?php

namespace benben\translation;

require_once ('ITranslation.php');

/**
 * @author benben
 * @version 1.0
 * @created 30-May-2013 4:50:16 PM
 */
class BenbenTranslation implements ITranslation
{

	/**
	 * 
	 * @param category
	 * @param message
	 * @param params
	 */
	public function translate($category, $message, array $params = array())
	{
	    return str_replace(array_keys($params), array_values($params), $message);
	}

}
