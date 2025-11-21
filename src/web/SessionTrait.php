<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use Yii;
use yii\web\Cookie;

trait SessionTrait
{
    /**
     * @var string|null the optional cookie domain
     */
    public ?string $cookieDomain = null;

    public function getCookieParams(): array
    {
        if ($this->cookieDomain === null) {
            $cookie = Yii::createObject(Cookie::class);
            $this->cookieDomain = $cookie->domain;
        }

        return [
            ...parent::getCookieParams(),
            'sameSite' => 'Lax',
            'domain' => $this->cookieDomain,
        ];
    }
}
