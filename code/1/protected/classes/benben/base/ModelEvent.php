<?php
namespace benben\base;

/**
 * ModelEvent class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


/**
 * ModelEvent class.
 *
 * ModelEvent represents the event parameters needed by events raised by a model.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package system.base
 * @since 1.0
 */
class ModelEvent extends Event
{
	/**
	 * @var boolean whether the model is in valid status and should continue its normal method execution cycles. Defaults to true.
	 * For example, when this event is raised in a {@link FormModel} object that is executing {@link Model::beforeValidate},
	 * if this property is set false by the event handler, the {@link Model::validate} method will quit after handling this event.
	 * If true, the normal execution cycles will continue, including performing the real validations and calling
	 * {@link Model::afterValidate}.
	 */
	public $isValid=true;
	/**
	 * @var DbCriteria the query criteria that is passed as a parameter to a find method of {@link CActiveRecord}.
	 * Note that this property is only used by {@link ActiveRecord::onBeforeFind} event.
	 * This property could be null.
	 * @since 1.1.5
	 */
	public $criteria;
}
