<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\models\SessionAuthKey;
use Yii;

/**
 * Class SessionTrait
 * @package davidhirtz\yii2\skeleton\web
 */
trait SessionTrait
{
    /**
     * @var string the optional cookie domain
     */
    public $cookieDomain;

    /**
     * @return array
     */
    public function getCookieParams()
    {
        if ($this->cookieDomain === null) {
            $this->cookieDomain = Yii::$app->params['cookieDomain'] ?? null;
        }

        /** @noinspection PhpUndefinedClassInspection */
        return array_merge(parent::getCookieParams(), array_filter([
            'domain' => $this->cookieDomain,
        ]));
    }

    /**
     * @inheritDoc
     */
    public function gcSession($maxLifetime)
    {
        SessionAuthKey::deleteAll('[[expire]]<:expire', [
            'expire' => time(),
        ]);

        /** @noinspection PhpUndefinedClassInspection */
        return parent::gcSession($maxLifetime);
    }
}