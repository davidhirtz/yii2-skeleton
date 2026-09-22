<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Yii;
use yii\base\InvalidRouteException;
use yii\helpers\Json;
use yii\web\Cookie;
use Hirtz\Skeleton\Helpers\CookieHelper;
use Hirtz\Skeleton\Helpers\Url;

;

class Response extends \yii\web\Response
{
    final public const string HTMX_REDIRECT_TARGET = '#wrap';

    /**
     * `null` leaves the swap to whatever issued the request; see {@see setHtmxRedirectTarget()}.
     */
    protected ?string $htmxRedirectTarget = self::HTMX_REDIRECT_TARGET;

    private bool $isHtmxReload = false;

    /**
     * Both htmx properties are per-response state, and `clear()` is what resets a response for the next one —
     * which only an application serving more than one request reaches, `Test\Browser` among them. Without this
     * a single `setHtmxReload()` turned every later response of the same test into an empty 200 carrying
     * `HX-Refresh`, so a login answered after one read as a login that had silently failed.
     */
    #[\Override]
    public function clear(): void
    {
        $this->htmxRedirectTarget = self::HTMX_REDIRECT_TARGET;
        $this->isHtmxReload = false;

        parent::clear();
    }

    #[\Override]
    protected function prepare(): void
    {
        if (Application::current()->getRequest()->getIsDraft()) {
            $this->getHeaders()->set('X-Robots-Tag', 'none');
        }

        if ($this->isHtmxReload && Application::current()->getRequest()->isHtmxRequest()) {
            $this->prepareHtmxReload();
        }

        $this->removeHostOnlyCookies();

        parent::prepare();
    }

    /**
     * A cookie's identity is its name *and* its scope, and PHP keeps the **first** of two the browser sends, which
     * is the stale one. So a host-only `_session` left over from an earlier scope of the installation silently
     * discards every session written under the configured `Domain`: each request reads the dead id, starts a fresh
     * session and writes a domain-scoped cookie nothing ever reads back — the admin language, the flashes and the
     * CSRF token a form was rendered with all die between requests, with nothing on the server to say why. Nothing
     * else clears the twin, since the application only ever writes the scoped cookie (monorepo issue #195).
     *
     * This is {@see \Hirtz\Skeleton\Web\User::removeIdentityCookie()} for the two cookies that have no logout to
     * hang the deletion on, and it is sent only for a name the request actually carried twice, so a healed browser
     * stops paying for it.
     */
    protected function removeHostOnlyCookies(): void
    {
        $request = Application::current()->getRequest();
        $duplicates = $request->getDuplicateCookieNames();

        if (!$duplicates) {
            return;
        }

        $session = Application::current()->getSession();
        $cookies = [];

        if (in_array($session->getName(), $duplicates, true)) {
            $cookies[] = Yii::$container->get(Cookie::class, [], [
                'name' => $session->getName(),
                'secure' => (bool)($session->getCookieParams()['secure'] ?? false),
            ]);
        }

        if ($request->enableCsrfCookie && in_array($request->csrfParam, $duplicates, true)) {
            $cookies[] = Yii::$container->get(Cookie::class, [], [
                ...$request->csrfCookie,
                'name' => $request->csrfParam,
            ]);
        }

        foreach ($cookies as $cookie) {
            if ($cookie->domain !== '') {
                $this->getHeaders()->add('Set-Cookie', CookieHelper::getExpiredHeader($cookie));
            }
        }
    }

    /**
     * `yii\web\Response::sendHeaders()` sends the first value of every header name with PHP's `$replace` set, and
     * for `Set-Cookie` that throws away every cookie already queued — including the session cookie PHP itself
     * emitted from `session_regenerate_id()`, which a login always triggers. The client then kept the session id
     * it came with, found no session behind it on the next request, and every flash died with it. A cookie the
     * application adds as a header is therefore appended after the rest, never sent through the collection.
     */
    #[\Override]
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
     * htmx reads `HX-Refresh` first and returns, so it must not be set beside the `HX-Redirect` a redirect issued
     * after {@see setHtmxReload()} has already answered with; `HX-Location` is the navigation this is the opposite
     * of and goes either way.
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
    #[\Override]
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
     * `HX-Location` is the navigation: it carries its own swap context and so overrides the `hx-select`, `hx-swap`
     * and `hx-select-oob` of whatever issued the request, which is what lets it replace the page and push the URL.
     * An action refreshing a region of the page the user is already on passes `null` instead, and is answered with
     * an ordinary redirect the requesting element follows itself, leaving all three of its attributes to apply.
     *
     * Which of the two it is only the action knows: a form targets itself so its validation errors land in place,
     * and still navigates away once it saves.
     */
    public function setHtmxRedirectTarget(?string $target): static
    {
        $this->htmxRedirectTarget = $target;
        return $this;
    }

    /**
     * Answers an htmx request with a fresh document instead of swapping into the current one: `HX-Redirect` for a
     * redirect issued after this, `HX-Refresh` for a response that stays where it is. The page was rendered for a
     * session that is over — the navbar and the flashes outside `#wrap` that no swap reaches among it, down to the
     * CSRF token it carries — so nothing of it can be kept.
     *
     * A request that is not htmx is answered as it would be anyway, so an action need not ask which it is.
     */
    public function setHtmxReload(): static
    {
        $this->isHtmxReload = true;
        return $this;
    }
}
