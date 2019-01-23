<?php
namespace davidhirtz\yii2\skeleton\composer;

use Yii;
use yii\authclient\Collection;
use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;
use yii\i18n\PhpMessageSource;

/**
 * Class Bootstrap
 * @package davidhirtz\yii2\skeleton\bootstrap
 */
class Installer extends \yii\composer\Installer
{
	/**
	 * @inheritdoc
	 */
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
	{
		parent::update($repo, $initial, $target);
		$this->createCredentials();
	}

	/**
	 * Creates credentials via composer install.
	 */
	public function createCredentials()
	{
		$config=func_get_args();

		if(!is_file($config))
		{
			echo $this->io->ask('Do you want to create the "config/credentials.php" file? (yes|no) [no]');
		}
	}

	public static function getUserInput()
	{
		$handle=fopen('php://stdin', 'r');
		$input=trim(fgets($handle));
		fclose($handle);

		return $input;
	}

}