<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Yii;
use yii\web\Cookie;

trait SessionTrait
{
    /**
     * @var string|null the optional cookie domain
     */
    public ?string $cookieDomain = null;

    /**
     * @var bool|null whether the session cookie is `Secure`, `null` derives it from the request. Pin it on a host
     * that answers on both schemes — see {@see \Hirtz\Skeleton\Web\User::$cookieSecure}.
     */
    public ?bool $cookieSecure = null;

    /**
     * @return array<string, mixed>
     */
    public function getCookieParams(): array
    {
        if ($this->cookieDomain === null) {
            $cookie = Yii::createObject(Cookie::class);
            $this->cookieDomain = $cookie->domain;
        }

        return [
            ...parent::getCookieParams(),
            'sameSite' => 'Lax',
            'secure' => $this->cookieSecure ?? Application::current()->getRequest()->getIsSecureConnection(),
            'domain' => $this->cookieDomain,
        ];
    }

    /**
     * Reading a session the request did not bring would start one, writing a row for every cookieless visitor.
     */
    public function get($key, $defaultValue = null): mixed
    {
        return $this->isStarted() ? parent::get($key, $defaultValue) : $defaultValue;
    }

    public function has($key): bool
    {
        return $this->isStarted() && parent::has($key);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllFlashes($delete = false): array
    {
        return $this->isStarted() ? parent::getAllFlashes($delete) : [];
    }

    /**
     * Yii's flash writers write `$_SESSION` themselves and relied on {@see get()} to start the session first.
     */
    public function setFlash($key, $value = true, $removeAfterAccess = true): void
    {
        $this->open();
        parent::setFlash($key, $value, $removeAfterAccess);
    }

    public function addFlash($key, $value = true, $removeAfterAccess = true): void
    {
        $this->open();
        parent::addFlash($key, $value, $removeAfterAccess);
    }

    public function removeFlash($key): mixed
    {
        return $this->isStarted() ? parent::removeFlash($key) : null;
    }

    public function isStarted(): bool
    {
        return $this->getIsActive() || $this->getHasSessionId();
    }
}
