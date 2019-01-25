<?php
namespace davidhirtz\yii2\skeleton\web;

use yii\base\Event;

/**
 * Class UrlManagerEvent
 * @package davidhirtz\yii2\skeleton\web
 */
class UrlManagerEvent extends Event
{
	/**
	 * @var string
	 */
	public $url;

	/**
	 * @var array
	 */
	public $params;

	/**
	 * @var Request
	 */
	public $request;
}