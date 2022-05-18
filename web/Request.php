<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\web\Cookie;

/**
 * The web Request class extends the default Yii class by a draft mode and the option to set the host info via application
 * params and the application language based on the user's preferences.
 */
class Request extends \yii\web\Request
{
    /**
     * @var bool whether user language should be saved for logged-in users (db) and guests (cookie).
     */
    public $setUserLanguage = true;

    /**
     * @var string|false the subdomain indicating a draft version of the application. Further validation should
     * be done on the controller level.
     */
    public $draftSubdomain = 'draft';

    /**
     * @var bool
     */
    private $_isDraft = false;

    /**
     * @var string|null
     */
    private $_serverHostInfo;

    /**
     * @var string|false
     */
    private $_draftHostInfo;

    /** Sets the host info via params after draft mode is checked. Setting the host info manually can be useful if multiple
     * domains link to a single website and the URLs (e.g., in the sitemap.xml) should be consistent or to prevent
     * faked header attacks (see https://www.acunetix.com/vulnerabilities/web/host-header-attack). The original
     * value of `$hostInfo` is still available via `Request::getRequestHostInfo()`.
     */
    public function init()
    {
        if ($this->enableCookieValidation) {
            $this->cookieValidationKey = $this->cookieValidationKey ?? Yii::$app->params['cookieValidationKey'] ?? null;
        }

        $this->_serverHostInfo = $this->getHostInfo();

        if ($this->draftSubdomain && strpos($this->_serverHostInfo, "//{$this->draftSubdomain}.") !== false) {
            $this->_isDraft = 1;
        }

        if ($hostInfo = Yii::$app->params['hostInfo'] ?? false) {
            $this->setHostInfo($hostInfo);
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
                            $identity->updateAttributes(['language' => $language]);
                        } elseif ($language != Yii::$app->sourceLanguage && $cookie !== $language) {
                            /** @noinspection PhpParamsInspection */
                            Yii::$app->getResponse()->getCookies()->add(Yii::createObject([
                                'class' => Cookie::class,
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
     * Returns whether this is an Ajax route request.
     * @return bool
     */
    public function getIsAjaxRoute()
    {
        return $this->getIsAjax() && ArrayHelper::getValue($_SERVER, 'HTTP_X_AJAX_REQUEST') == 'route';
    }

    /**
     * @return string
     */
    public function getProductionHostInfo()
    {
        return $this->getIsDraft() ? str_replace("//{$this->draftSubdomain}.", '//', $this->getHostInfo()) : $this->getHostInfo();
    }

    /**
     * @return string|null the host info as implemented by Yii's {@see \yii\web\Request::getHostInfo()}
     */
    public function getServerHostInfo()
    {
        return $this->_serverHostInfo;
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
            $hostInfo = $this->getHostInfo();
            $this->_draftHostInfo = $this->getIsDraft() && $this->draftSubdomain && strpos($hostInfo, $this->draftSubdomain) !== false ? $hostInfo : ($this->draftSubdomain ? preg_replace('#^((https?://)(www.)?)#', "$2{$this->draftSubdomain}.", $hostInfo) : false);
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