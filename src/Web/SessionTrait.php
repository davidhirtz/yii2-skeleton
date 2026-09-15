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
            'secure' => $this->cookieSecure ?? Yii::$app->getRequest()->getIsSecureConnection(),
            'domain' => $this->cookieDomain,
        ];
    }
}
