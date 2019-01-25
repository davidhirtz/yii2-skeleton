<?php
namespace davidhirtz\yii2\skeleton\web;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Class Application
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property \yii\authclient\Collection $authClientCollection
 * @property AssetManager $assetManager
 * @property \yii\rbac\DbManager $authManager
 * @property \davidhirtz\yii2\skeleton\i18n\I18N $i18n
 * @property Request $request
 * @property DbSession $session
 * @property Sitemap $sitemap
 * @property UrlManager $urlManager
 * @property User $user
 * @property View $view
 *
 * @method AssetManager getAssetManager()
 * @method \yii\rbac\DbManager getAuthManager()
 * @method \davidhirtz\yii2\skeleton\i18n\I18N getI18n()
 * @method Request getRequest()
 * @method DbSession getSession()
 * @method UrlManager getUrlManager()
 * @method User getUser()
 * @method View getView()
 */
class Application extends \yii\web\Application
{
	/**
	 * @var string
	 */
	public $cookieConfig='@app/config/cookie.php';

	/**
	 * @param array $config
	 * @throws \yii\base\InvalidConfigException
	 */
	public function preInit(&$config)
	{
		if(!isset($config['id']))
		{
			$config['id']='skeleton';
		}

		if(!isset($config['components']['request']['cookieValidationKey']))

//		dump($config,1);

//		$config['components']['db']=[
//			'class'=>'yii\db\Connection',
//			'enableSchemaCache'=>true,
//			'charset'=>'utf8mb4',
//		];

		//$defaults=require(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'defaults.php');
		//$config=ArrayHelper::merge($defaults, $config);

		parent::preInit($config);
	}

	protected function bootstrap()
	{
		parent::bootstrap();
		$this->enableCookieValidation();
	}

	/**
	 * Loads cookie validation key from params.
	 */
	protected function enableCookieValidation()
	{
		$request=$this->getRequest();
		dump('enableCookieValidation');

		if($request->enableCookieValidation && !$request->cookieValidationKey)
		{
			if(!isset($this->params['cookieValidationKey']))
			{
				throw new InvalidConfigException(get_class($request).'::cookieValidationKey must be configured with a secret key.');
			}

			$request->cookieValidationKey=$this->params['cookieValidationKey'];
			unset($this->params['cookieValidationKey']);
		}
	}

	/**
	 * @return array
	 */
	public function coreComponents()
	{
		return array_merge(parent::coreComponents(), [
			'db'=>['class'=>'yii\db\Connection'],
			'cache'=>['class'=>'yii\caching\FileCache'],
			'i18n'=>['class'=>'davidhirtz\yii2\skeleton\i18n\I18n'],
			'request'=>['class'=>'davidhirtz\yii2\skeleton\web\Request'],
			'session'=>['class'=>'davidhirtz\yii2\skeleton\web\DbSession'],
			'user'=>['class'=>'davidhirtz\yii2\skeleton\web\User'],
			'urlManager'=>['class'=>'davidhirtz\yii2\skeleton\web\UrlManager'],
			'view'=>['class'=>'davidhirtz\yii2\skeleton\web\View'],
		]);
	}
}