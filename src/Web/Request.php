<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Helpers\ArrayHelper;
use Override;
use Yii;

/**
 * The web Request class extends the default Yii class by a draft mode and the option to set the host info via
 * application params.
 */
class Request extends \yii\web\Request
{
    /**
     * @var string the parameter name used to add the language to a URL via `UrlManager::$i18nUrl`.
     */
    public string $languageParam = 'language';

    private bool $_isDraft = false;

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

        parent::init();
    }

    #[Override]
    public function getRemoteIP(): ?string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? parent::getRemoteIP();
    }

    public function getIsAjaxRoute(): bool
    {
        return $this->getIsAjax() && ArrayHelper::getValue($_SERVER, 'HTTP_X_AJAX_REQUEST') === 'route';
    }

    public function isDraftRequest(): bool
    {
        $subdomain = Yii::$app->getUrlManager()->draftSubdomain;
        return $subdomain && str_contains((string)$this->getHostInfo(), "//$subdomain.");
    }

    public function isHtmxRequest(): bool
    {
        return $this->getHeaders()->has('HX-Request');
    }

    public function getIsDraft(): bool
    {
        return $this->_isDraft;
    }

    public function setIsDraft(bool $isDraft): void
    {
        $this->_isDraft = $isDraft;
    }

    public function preferNoContent(): bool
    {
        $prefer = (string)$this->getHeaders()->get('prefer');
        return str_contains(strtolower($prefer), 'status=204');
    }
}
