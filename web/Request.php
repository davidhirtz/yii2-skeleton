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
     * @var bool whether user language should be saved for logged in users (db) and guests (cookie).
     */
    public $setUserLanguage = true;

    /**
     * @var string the subdomain indicating a draft version of the application. Further validation should
     * be done on the controller level.
     */
    public $draftSubdomain = 'draft';

    /**
     * @var string
     */
    private $_draftHostInfo;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->enableCookieValidation && !$this->cookieValidationKey && isset(Yii::$app->params['cookieValidationKey'])) {
            $this->cookieValidationKey = Yii::$app->params['cookieValidationKey'];
        }

        dump($this->getDraftHostInfo());

        parent::init();
    }

    /**
     * Sets application language based on request.
     */
    public function resolve()
    {
        if (count($languages = Yii::$app->getI18n()->getLanguages()) > 1) {
            $manager = Yii::$app->getUrlManager();

            if (!$manager->i18nUrl && !$manager->i18nSubdomain) {
                $param = $manager->languageParam;
                $cookie = $this->getCookies()->getValue($param);
                $identity = Yii::$app->getUser()->getIdentity();

                if (!$language = $this->post($manager->languageParam, $this->get($param, $identity ? $identity->language : $cookie))) {
                    $language = $this->getPreferredLanguage($languages);
                }

                if (in_array($language, $languages)) {
                    if ($this->setUserLanguage) {
                        if ($identity) {
                            $identity->updateAttributes([
                                'language' => $language,
                            ]);
                        } elseif ($language != Yii::$app->sourceLanguage && $cookie !== $language) {
                            Yii::$app->getResponse()->getCookies()->add(Yii::createObject([
                                'class' => \yii\web\Cookie::class,
                                'name' => $param,
                                'value' => $language,
                            ]));
                        }
                    }

                    Yii::$app->language = $language;
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
        return $this->getIsAjax() && ArrayHelper::getValue($_SERVER, 'HTTP_X_AJAX_REQUEST') == 'route';
    }

    /**
     * @return string
     */
    public function getDraftHostInfo()
    {
        if ($this->_draftHostInfo === null) {
            $this->_draftHostInfo = preg_replace('#^((https?://)(www.)?)#', "$2{$this->draftSubdomain}.", parent::getHostInfo());
        }

        return $this->_draftHostInfo;
    }

    /**
     * @param string $draftHostInfo
     */
    public function setDraftHostInfo($draftHostInfo)
    {
        $this->_draftHostInfo = $draftHostInfo;
    }

    /**
     * @return bool
     */
    public function getIsDraft()
    {
        return strpos($this->getHostName(), $this->draftSubdomain) === 0;
    }
}