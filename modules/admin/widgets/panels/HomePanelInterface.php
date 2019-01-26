<?php
namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

/**
 * Class NavBarInterface.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets
 */
interface HomePanelInterface
{
	/**
	 * @return string
	 */
	public static function getTitle();

	/**
	 * @return array
	 */
	public static function getListItems();
}