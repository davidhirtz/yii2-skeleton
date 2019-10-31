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
     * @var string|false the subdomain indicating a draft version of the application. Further validation should
     * be done on the controller level.
     */
    public $draftSubdomain = 'draft';

    /**
     * @var string|false
     */
    private $_draftHostInfo;

    /**
     * @var bool
     */
    private $_isDraft = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->enableCookieValidation && !$this->cookieValidationKey && isset(Yii::$app->params['cookieValidationKey'])) {
            $this->cookieValidationKey = Yii::$app->params['cookieValidationKey'];
        }

        if ($this->draftSubdomain && strpos($hostInfo = $this->getHostInfo(), $subdomain = "//{$this->draftSubdomain}.") !== false) {
            $this->setHostInfo(str_replace($subdomain, '//', $hostInfo));
            $this->_isDraft = 1;
        }

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
     * Creates the draft URL by trying to replace existing "www" or adding the $draftSubdomain as
     * the first subdomain to the host.
     *
     * @return string
     */
    public function getDraftHostInfo()
    {
        if ($this->_draftHostInfo === null) {
            $this->_draftHostInfo = $this->draftSubdomain ? preg_replace('#^((https?://)(www.)?)#', "$2{$this->draftSubdomain}.", $this->getHostInfo()) : false;
        }

        return $this->_draftHostInfo;
    }

    /**
     * @return bool
     */
    public function getIsDraft()
    {
        return $this->_isDraft;
    }
}