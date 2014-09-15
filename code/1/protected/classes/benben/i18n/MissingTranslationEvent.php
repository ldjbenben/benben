<?php
namespace benben\i18n;
use benben\base\Event;
/**
 * MissingTranslationEvent represents the parameter for the {@link MessageSource::onMissingTranslation onMissingTranslation} event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package system.i18n
 * @since 1.0
 */
class MissingTranslationEvent extends Event
{
	/**
	 * @var string the message to be translated
	 */
	public $message;
	/**
	 * @var string the category that the message belongs to
	 */
	public $category;
	/**
	 * @var string the ID of the language that the message is to be translated to
	 */
	public $language;

	/**
	 * Constructor.
	 * @param mixed $sender sender of this event
	 * @param string $category the category that the message belongs to
	 * @param string $message the message to be translated
	 * @param string $language the ID of the language that the message is to be translated to
	 */
	public function __construct($sender,$category,$message,$language)
	{
		parent::__construct($sender);
		$this->message=$message;
		$this->category=$category;
		$this->language=$language;
	}
}