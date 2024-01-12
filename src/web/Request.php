<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;

/**
 * The web Request class extends the default Yii class by a draft mode and the option to set the host info via
 * application params and the application language based on the user's preferences.
 */
class Request extends \yii\web\Request
{
    /**
     * @var string|false the subdomain indicating a draft version of the application. Further validation should
     * be done on the controller level.
     */
    public string|false $draftSubdomain = 'draft';

    /**
     * @var string the name of the GET parameter that specifies the language.
     */
    public string $languageParam = 'language';

    private bool $_isDraft = false;
    private string|false|null $_draftHostInfo = null;
    private ?string $_language = null;

    /**
     * Sets the host info via params after draft mode is checked. Setting the host info manually can be useful if
     * multiple domains link to a single website and the URLs (e.g., in the sitemap.xml) should be consistent or to
     * prevent faked header attacks (see https://www.acunetix.com/vulnerabilities/web/host-header-attack). The original
     * value of `$hostInfo` is still available via `Request::getRequestHostInfo()`.
     */
    public function init(): void
    {
        if ($this->enableCookieValidation && !$this->cookieValidationKey) {
            $this->cookieValidationKey = Yii::$app->params['cookieValidationKey'] ?? '';
        }

        if ($this->draftSubdomain && str_contains($this->getHostInfo(), "//$this->draftSubdomain.")) {
            $this->_isDraft = true;
        }

        parent::init();
    }

    public function getLanguage(): ?string
    {
        $this->_language ??= $this->post($this->languageParam);
        $this->_language ??= $this->get($this->languageParam);
        $this->_language ??= $this->getLanguageFromCookie();

        return $this->_language;
    }

    public function getLanguageFromCookie(): ?string
    {
        return $this->getCookies()->getValue($this->languageParam);
    }

    public function getRemoteIP(): ?string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? parent::getRemoteIP();
    }

    public function getIsAjaxRoute(): bool
    {
        return $this->getIsAjax() && ArrayHelper::getValue($_SERVER, 'HTTP_X_AJAX_REQUEST') == 'route';
    }

    public function getProductionHostInfo(): string
    {
        return $this->getIsDraft()
            ? str_replace("//$this->draftSubdomain.", '//', $this->getHostInfo())
            : $this->getHostInfo();
    }

    /**
     * Creates the draft URL by trying to replace existing "www" or adding the $draftSubdomain as the first subdomain to
     * the host.
     */
    public function getDraftHostInfo(): bool|string
    {
        $this->_draftHostInfo ??= $this->getIsDraft() && $this->draftSubdomain && str_contains($this->getHostInfo(), $this->draftSubdomain)
            ? $this->getHostInfo()
            : ($this->draftSubdomain ? preg_replace('#^((https?://)(www.)?)#', "$2$this->draftSubdomain.", $this->getHostInfo()) : false);

        return $this->_draftHostInfo;
    }

    public function getIsDraft(): bool
    {
        return $this->_isDraft;
    }
}
