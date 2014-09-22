<?php

namespace application\models;

use benben\db\ar\ActiveRecord;

class UserModel extends ActiveRecord 
{
	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param $className string active record class name.
	 * @return \application\models\UserModel the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
	
	/**
	 *
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{user}}';
	}
	
	public function rules()
	{
		return array(
				array('username,email,password', 'required'),
		);
	}
	
}