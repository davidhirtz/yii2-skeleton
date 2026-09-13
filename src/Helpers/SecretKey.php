<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Yii;
use yii\base\InvalidConfigException;

/**
 * The application secret behind everything that is keyed but is not a password — the HMAC of a user token, the
 * encrypted two-factor secret. It falls back to the cookie validation key, which every installation already has,
 * so nothing breaks before `secretKey` is set; losing or changing it invalidates whatever was written with it.
 */
class SecretKey
{
    /**
     * @throws InvalidConfigException
     */
    public static function get(): string
    {
        $key = Yii::$app->params['secretKey'] ?? Yii::$app->params['cookieValidationKey'] ?? null;

        if (!$key) {
            throw new InvalidConfigException('Either `secretKey` or `cookieValidationKey` must be set in params.');
        }

        return (string)$key;
    }
}
