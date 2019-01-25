<?php
namespace davidhirtz\yii2\skeleton\console;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;

/**
 * Class Application
 * @package davidhirtz\yii2\skeleton\console
 */
class Application extends \yii\console\Application
{
	/**
	 * @param array $config
	 * @throws \yii\base\InvalidConfigException
	 */
	public function preInit(&$config)
	{
		$defaults=require(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'defaults.php');
		$config=ArrayHelper::merge($defaults, $config);

		$this->removeWebApplicationConfig($config);


		parent::preInit($config);
	}

	/**
	 * @inheritdoc
	 */
	protected function bootstrap()
	{
		Yii::setAlias('@webroot', '@app/web');
		Yii::setAlias('@web', '@app');

		parent::bootstrap();
	}

	/**
	 * @inheritdoc
	 */
	public function coreCommands()
	{
		return array_merge(parent::coreCommands(), [
			'asset'=>'davidhirtz\yii2\skeleton\console\controllers\AssetController',
			'migrate'=>[
				'class'=>'yii\console\controllers\MigrateController',
				'migrationPath'=>null,
				'migrationNamespaces'=>[
					'app\migrations',
					'davidhirtz\yii2\skeleton\migrations',
				],
			],
		]);
	}

	/**
	 * @param array $config
	 */
	protected function removeWebApplicationConfig(&$config)
	{
		unset($config['components']['errorHandler']['errorAction']);
		unset($config['components']['request']['cookieValidationKey']);
		unset($config['components']['session']);
		unset($config['components']['user']);
	}
}