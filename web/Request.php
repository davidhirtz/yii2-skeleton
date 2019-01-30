<?php
namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;

/**
 * Class Request
 * @package davidhirtz\yii2\skeleton\web
 */
class Request extends \yii\web\Request
{
	/**
	 * @var bool
	 */
	public $setUserLanguage=true;

	/**
	 * @var string
	 */
	public $cdnUrl='/';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if($this->enableCookieValidation && !$this->cookieValidationKey && isset(Yii::$app->params['cookieValidationKey']))
		{
			$this->cookieValidationKey=Yii::$app->params['cookieValidationKey'];
		}

		parent::init();
	}

	/**
	 * Sets application language based on request.
	 */
	public function resolve()
	{
		if(count($languages=Yii::$app->getI18n()->getLanguages())>1)
		{
			$manager=Yii::$app->getUrlManager();

			if(!$manager->i18nUrl)
			{
				$param=$manager->languageParam;
				$cookie=$this->getCookies()->getValue($param);
				$identity=Yii::$app->getUser()->getIdentity();

				if(!$language=$this->post($manager->languageParam, $this->get($param, $identity ? $identity->language : $cookie)))
				{
					$language=$this->getPreferredLanguage($languages);
				}

				if(in_array($language, $languages))
				{
					if($this->setUserLanguage)
					{
						if($identity)
						{
							$identity->updateAttributes([
								'language'=>$language,
							]);
						}
						elseif($language!=Yii::$app->sourceLanguage && $cookie!==$language)
						{
							Yii::$app->getResponse()->getCookies()->add(new \yii\web\Cookie([
								'name'=>$param,
								'value'=>$language,
							]));
						}
					}

					Yii::$app->language=$language;
				}
			}
		}

		return parent::resolve();
	}

	/**
	 * @inheritdoc
	 */
	public function getUserIP()
	{
		return ArrayHelper::getValue($_SERVER, 'HTTP_X_FORWARDED_FOR', ArrayHelper::getValue($_SERVER, 'HTTP_CLIENT_IP', parent::getUserIP()));
	}

	/**
	 * Returns whether this is a Ajax route request.
	 * @return bool
	 */
	public function getIsAjaxRoute()
	{
		return $this->getIsAjax() && ArrayHelper::getValue($_SERVER, 'HTTP_X_AJAX_REQUEST')=='route';
	}
}