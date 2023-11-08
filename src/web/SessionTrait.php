<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;

trait SessionTrait
{
    /**
     * @var string|null the optional cookie domain
     */
    public ?string $cookieDomain = null;

    public function getCookieParams(): array
    {
        $this->cookieDomain ??= Yii::$app->params['cookieDomain'] ?? null;

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return array_merge(parent::getCookieParams(), array_filter([
            'domain' => $this->cookieDomain,
        ]));
    }
}
