<?php
namespace davidhirtz\yii2\skeleton\composer;

/**
 * Class Bootstrap
 * @package davidhirtz\yii2\skeleton\bootstrap
 */
class Installer extends \yii\composer\Installer
{
	/**
	 * @param \Composer\Script\Event $event
	 */
	public static function postInstall($event)
	{
		$event->getIO()->writeError('<info>Bin da....</info>');
		parent::postInstall($event);
	}

	/**
	 * @inheritdoc
	 */
	public static function generateCookieValidationKey()
	{
		$config='config/params.php';

		if(!is_file($config))
		{
			$key=self::generateRandomString();
			file_put_contents($config, <<<EOF
<?php
return [
	'cookieValidationKey'=>"$key",
];
EOF
			);

		}
		else
		{
			parent::generateCookieValidationKey([$config]);
		}
	}
}