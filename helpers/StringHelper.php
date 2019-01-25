<?php
namespace davidhirtz\yii2\skeleton\helpers;

/**
 * Class StringHelper.
 * @package davidhirtz\yii2\skeleton\helpers
 */
class StringHelper extends \yii\helpers\BaseStringHelper
{
	/**
	 * @param int|string $minutes
	 * @param string $format
	 * @return string
	 */
	public static function formatMinutes($minutes, $format = '%02d:%02d')
	{
		if(is_numeric($minutes))
		{
			$hours=floor($minutes/60);
			$minutes=$minutes%60;

			return sprintf($format, $hours, $minutes);
		}

		return $minutes;
	}

	/**
	 * @param string $ip
	 * @return string
	 */
	public static function ip2Long($ip)
	{
		return sprintf('%u', ip2long($ip));
	}
}