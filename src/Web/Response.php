<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Override;
use Yii;
use yii\base\InvalidRouteException;
use yii\helpers\Json;
use yii\web\Cookie;
use Hirtz\Skeleton\Helpers\CookieHelper;
use Hirtz\Skeleton\Helpers\Url;

class Response extends \yii\web\Response
{
    final public const string HTMX_REDIRECT_TARGET = '#wrap';

    /**
     * `null` leaves the swap to whatever issued the request; see {@see setHtmxRedirectTarget()}.
     */
    protected ?string $htmxRedirectTarget = self::HTMX_REDIRECT_TARGET;

    private bool $isHtmxReload = false;

    /**
     * Without the htmx reset, one `setHtmxReload()` made every later response of `Test\Browser` an empty `HX-Refresh`.
     */
    #[Override]
    public function clear(): void
    {
        $this->htmxRedirectTarget = self::HTMX_REDIRECT_TARGET;
        $this->isHtmxReload = false;

        parent::clear();
    }

    #[Override]
    protected function prepare(): void
    {
        if (Application::current()->getRequest()->getIsDraft()) {
            $this->getHeaders()->set('X-Robots-Tag', 'none');
        }

        if ($this->isHtmxReload && Application::current()->getRequest()->isHtmxRequest()) {
            $this->prepareHtmxReload();
        }

        $this->removeCookieTwins();

        parent::prepare();
    }

    /**
     * PHP keeps the first, stale twin of a cookie from an earlier scope, which discards every session written under
     * the configured `Domain` (#195).
     */
    protected function removeCookieTwins(): void
    {
        $request = Application::current()->getRequest();
        $duplicates = $request->getDuplicateCookieNames();

        if (!$duplicates) {
            return;
        }

        $session = Application::current()->getSession();
        $cookies = [];

        if (in_array($session->getName(), $duplicates, true)) {
            $params = $session->getCookieParams();

            $cookies[] = Yii::$container->get(Cookie::class, [], [
                'name' => $session->getName(),
                'domain' => (string)($params['domain'] ?? ''),
                'secure' => (bool)($params['secure'] ?? false),
            ]);
        }

        if ($request->enableCsrfCookie && in_array($request->csrfParam, $duplicates, true)) {
            $cookies[] = Yii::$container->get(Cookie::class, [], [
                ...$request->csrfCookie,
                'name' => $request->csrfParam,
            ]);
        }

        $host = (string)$request->getHostName();

        foreach ($cookies as $cookie) {
            foreach (CookieHelper::getExpiredTwinHeaders($cookie, $host) as $header) {
                $this->getHeaders()->add('Set-Cookie', $header);
            }
        }
    }

    /**
     * Yii sends `Set-Cookie` with `$replace`, dropping the cookie `session_regenerate_id()` queued, so header cookies
     * are appended after the rest.
     */
    #[Override]
    protected function sendHeaders(): void
    {
        $cookies = $this->getHeaders()->remove('Set-Cookie');

        parent::sendHeaders();

        foreach ((array)$cookies as $cookie) {
            $this->sendCookieHeader((string)$cookie);
        }
    }

    protected function sendCookieHeader(string $cookie): void
    {
        header("Set-Cookie: $cookie", false);
    }

    /**
     * htmx reads `HX-Refresh` first and returns, so it must not sit beside an `HX-Redirect` already set.
     */
    protected function prepareHtmxReload(): void
    {
        $headers = $this->getHeaders();
        $headers->remove('HX-Location');

        if (!$headers->has('HX-Redirect')) {
            $headers->set('HX-Refresh', 'true');
        }

        $this->format = self::FORMAT_HTML;
        $this->data = null;
        $this->content = '';

        $this->setStatusCode(200);
    }

    /**
     * @param array<int|string, mixed>|string $url
     */
    #[Override]
    public function redirect($url, $statusCode = 302, $checkAjax = true): static
    {
        if (is_array($url) && isset($url[0])) {
            $url[0] = '/' . ltrim((string) $url[0], '/');
        }

        $request = Application::current()->getRequest();
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

        if ($request->isHtmxRequest() && $this->isHtmxReload) {
            $headers->set('HX-Redirect', $url);
            return $this->setStatusCode(200);
        }

        if ($request->isHtmxRequest() && $this->htmxRedirectTarget !== null) {
            $headers->set('HX-Location', Json::encode([
                'path' => $url,
                'target' => $this->htmxRedirectTarget,
            ]));

            return $this->setStatusCode(200);
        }

        $headers->set($checkAjax && $request->getIsAjax() ? 'X-Redirect' : 'Location', $url);
        return $this->setStatusCode($statusCode);
    }

    /**
     * `HX-Location` overrides the requester's `hx-select`, `hx-swap` and `hx-select-oob`; an action refreshing a region
     * in place passes `null` for an ordinary redirect.
     */
    public function setHtmxRedirectTarget(?string $target): static
    {
        $this->htmxRedirectTarget = $target;
        return $this;
    }

    /**
     * `HX-Redirect` for a redirect issued after this, `HX-Refresh` otherwise; inert for a request that is not htmx.
     */
    public function setHtmxReload(): static
    {
        $this->isHtmxReload = true;
        return $this;
    }
}
