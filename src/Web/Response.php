<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Yii;
use yii\base\InvalidRouteException;
use yii\helpers\Json;
use Hirtz\Skeleton\Helpers\Url;

;

class Response extends \yii\web\Response
{
    protected string $htmxRedirectTarget = '#wrap';

    private bool $isHtmxRefresh = false;

    #[\Override]
    protected function prepare(): void
    {
        if (Yii::$app->getRequest()->getIsDraft()) {
            $this->getHeaders()->set('X-Robots-Tag', 'none');
        }

        if ($this->isHtmxRefresh) {
            $this->prepareHtmxRefresh();
        }

        parent::prepare();
    }

    /**
     * htmx reads `HX-Location` first and returns, so the redirect headers have to go for the refresh to happen.
     */
    protected function prepareHtmxRefresh(): void
    {
        $headers = $this->getHeaders();
        $headers->remove('HX-Location');
        $headers->remove('HX-Redirect');
        $headers->set('HX-Refresh', 'true');

        $this->format = self::FORMAT_HTML;
        $this->data = null;
        $this->content = '';

        $this->setStatusCode(200);
    }

    #[\Override]
    public function redirect($url, $statusCode = 302, $checkAjax = true): static
    {
        if (is_array($url) && isset($url[0])) {
            $url[0] = '/' . ltrim((string) $url[0], '/');
        }

        $request = Yii::$app->getRequest();
        $url = Url::to($url);

        if (preg_match('/\n/', $url)) {
            throw new InvalidRouteException("Route with new line character detected '$url'.");
        }

        if (
            str_starts_with($url, '/')
            && !str_starts_with($url, '//')
        ) {
            $url = $request->getHostInfo() . $url;
        }

        $headers = $this->getHeaders();

        if ($request->isHtmxRequest()) {
            $headers->set('HX-Location', Json::encode([
                'path' => $url,
                'target' => $this->htmxRedirectTarget,
            ]));

            return $this->setStatusCode(200);
        }

        $headers->set($checkAjax && $request->getIsAjax() ? 'X-Redirect' : 'Location', $url);
        return $this->setStatusCode($statusCode);
    }

    public function setHtmxRedirectTarget(string $target): static
    {
        $this->htmxRedirectTarget = $target;
        return $this;
    }

    /**
     * Answers an htmx request by reloading the page instead of swapping into it. The page was rendered for a session
     * that is gone, down to the CSRF token it carries, so nothing of it can be kept.
     */
    public function setHtmxRefresh(): static
    {
        $this->isHtmxRefresh = true;
        return $this;
    }
}
