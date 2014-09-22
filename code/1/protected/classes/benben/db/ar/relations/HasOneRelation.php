<?php
namespace benben\db\ar\relations;

/**
 * HasOneRelation represents the parameters specifying a HAS_ONE relation.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.db.ar
 * @since 1.0
 */
class HasOneRelation extends ActiveRelation
{
	/**
	 * @var string the name of the relation that should be used as the bridge to this relation.
	 * Defaults to null, meaning don't use any bridge.
	 * @since 1.1.7
	 */
	public $through;
}