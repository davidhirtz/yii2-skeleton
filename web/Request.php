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
    public bool $setUserLanguage = true;

    /**
     * @var string|false the subdomain indicating a draft version of the application. Further validation should
     * be done on the controller level.
     */
    public string|false $draftSubdomain = 'draft';

    /**
     * @var bool
     */
    private bool $_isDraft = false;

    /**
     * @var string|false
     */
    private string|false|null $_draftHostInfo = null;

    /**
     * Sets the host info via params after draft mode is checked. Setting the host info manually can be useful if multiple
     * domains link to a single website and the URLs (e.g., in the sitemap.xml) should be consistent or to prevent
     * faked header attacks (see https://www.acunetix.com/vulnerabilities/web/host-header-attack). The original
     * value of `$hostInfo` is still available via `Request::getRequestHostInfo()`.
     */
    public function init(): void
    {
        if ($this->enableCookieValidation) {
            $this->cookieValidationKey ??= Yii::$app->params['cookieValidationKey'] ?? null;
        }

        if ($this->draftSubdomain && str_contains($this->getHostInfo(), "//$this->draftSubdomain.")) {
            $this->_isDraft = 1;
        }

        parent::init();
    }

    /**
     * Sets application language based on request.
     */
    public function resolve(): array
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

    public function getIsAjaxRoute(): bool
    {
        return $this->getIsAjax() && ArrayHelper::getValue($_SERVER, 'HTTP_X_AJAX_REQUEST') == 'route';
    }

    public function getProductionHostInfo(): string
    {
        return $this->getIsDraft() ? str_replace("//$this->draftSubdomain.", '//', $this->getHostInfo()) : $this->getHostInfo();
    }

    /**
     * Creates the draft URL by trying to replace existing "www" or adding the $draftSubdomain as
     * the first subdomain to the host.
     */
    public function getDraftHostInfo(): bool|string
    {
        if ($this->_draftHostInfo === null) {
            $hostInfo = $this->getHostInfo();
            $this->_draftHostInfo = $this->getIsDraft() && $this->draftSubdomain && str_contains($hostInfo, $this->draftSubdomain)
                ? $hostInfo
                : ($this->draftSubdomain ? preg_replace('#^((https?://)(www.)?)#', "$2$this->draftSubdomain.", $hostInfo) : false);
        }

        return $this->_draftHostInfo;
    }

    public function getIsDraft(): bool
    {
        return $this->_isDraft;
    }
}