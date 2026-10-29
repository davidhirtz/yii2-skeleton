<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Falls back to the cookie validation key; changing it invalidates every user token and two-factor secret.
 */
class SecretKey
{
    public static function get(): string
    {
        $key = Yii::$app->params['secretKey'] ?? Yii::$app->params['cookieValidationKey'] ?? null;

        if (!$key) {
            throw new InvalidConfigException('Either `secretKey` or `cookieValidationKey` must be set in params.');
        }

        return (string)$key;
    }

    /**
     * Encrypted under the secret key and base64 encoded, so the result fits a text column.
     */
    public static function encrypt(string $data): string
    {
        return base64_encode(Yii::$app->getSecurity()->encryptByKey($data, static::get()));
    }

    /**
     * `null` for data not written by {@see static::encrypt()} under the current key.
     */
    public static function decrypt(string $data): ?string
    {
        $data = base64_decode($data, true);
        $decrypted = $data === false ? false : Yii::$app->getSecurity()->decryptByKey($data, static::get());

        return is_string($decrypted) ? $decrypted : null;
    }

    /**
     * @param positive-int $length
     */
    public static function generate(int $length = 32): string
    {
        return substr(rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '='), 0, $length);
    }
}
