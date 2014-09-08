<?php


namespace benben\translation;


/**
 * @author benben
 * @version 1.0
 * @created 30-May-2013 4:50:07 PM
 */
interface ITranslation
{

	/**
	 * 
	 * @param category
	 * @param message
	 * @param params
	 */
	public function translate($category, $message, array $params = array());

}
?>