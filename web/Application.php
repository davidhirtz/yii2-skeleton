<?php
namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;

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
	 * @param array $config
	 * @throws \yii\base\InvalidConfigException
	 */
	public function preInit(&$config)
	{
		$defaults=[
//			'id'=>'skeleton',
//			'bootstrap'=>[
//				function()
//				{
//					// Enable cookie validation here as extension's bootstrap will already
//					// initialized at this point, but user defined modules will be loaded after.
//					$this->enableCookieValidation();
//				}
//			],
//			'modules'=>[
//				'admin'=>[
//					'class'=>'davidhirtz\yii2\admin\Module',
//				],
//			]
		];

		$config=ArrayHelper::merge($defaults, $config);

		parent::preInit($config);
	}

	/**
	 * Loads cookie validation key from params.
	 */
//	protected function enableCookieValidation()
//	{
//		$request=$this->getRequest();
//
//		if($request->enableCookieValidation && !$request->cookieValidationKey)
//		{
//			if(!isset($this->params['cookieValidationKey']))
//			{
//				throw new InvalidConfigException(get_class($request).'::cookieValidationKey must be configured with a secret key.');
//			}
//
//			$request->cookieValidationKey=$this->params['cookieValidationKey'];
//			unset($this->params['cookieValidationKey']);
//		}
//	}

	/**
	 * @return \davidhirtz\yii2\admin\Module|\yii\base\Module
	 */
	public function getAdminModule()
	{
		return $this->getModule('admin');
	}

	/**
	 * @return array
	 */
	public function coreComponents()
	{
		return array_merge(parent::coreComponents(), [
			'i18n'=>['class'=>'davidhirtz\yii2\skeleton\i18n\I18n'],
			'request'=>['class'=>'davidhirtz\yii2\skeleton\web\Request'],
			'session'=>['class'=>'davidhirtz\yii2\skeleton\web\DbSession'],
			'user'=>['class'=>'davidhirtz\yii2\skeleton\web\User'],
			'urlManager'=>['class'=>'davidhirtz\yii2\skeleton\web\UrlManager'],
			'view'=>['class'=>'davidhirtz\yii2\skeleton\web\View'],
		]);
	}
}