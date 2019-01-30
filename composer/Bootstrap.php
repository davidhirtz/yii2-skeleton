<?php
namespace davidhirtz\yii2\skeleton\composer;

use Yii;
use yii\base\BootstrapInterface;

/**
 * Class Bootstrap
 * @package davidhirtz\yii2\skeleton\bootstrap
 */
class Bootstrap implements BootstrapInterface
{
	/**
	 * Shared application configuration after init.
	 *
	 * @param \davidhirtz\yii2\skeleton\web\Application|\davidhirtz\yii2\skeleton\console\Application $app
	 * @throws \yii\base\InvalidConfigException
	 */
	public function bootstrap($app)
	{
		Yii::setAlias('@skeleton', dirname(dirname(__FILE__)));
		Yii::setAlias('@bower', '@vendor/bower-asset');
		Yii::setAlias('@npm', '@vendor/npm-asset');

		if(!$app->has('cache'))
		{
			$app->set('cache', [
				'class'=>'yii\caching\FileCache',
			]);
		}

		if(!$app->has('db'))
		{
			if(file_exists($db=Yii::getAlias('@app/config/db.php')))
			{
				$config=[
					'class'=>'yii\db\Connection',
					'enableSchemaCache'=>true,
				];

				$app->set('db', array_merge($config, require($db)));
			}
		}

		if($app->has('db') && !$app->getDb()->charset)
		{
			$app->getDb()->charset='utf8mb4';
		}
	}
}