<?php

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

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return array_merge(parent::getCookieParams(), [
            'domain' => $this->cookieDomain,
        ]);
    }
}
