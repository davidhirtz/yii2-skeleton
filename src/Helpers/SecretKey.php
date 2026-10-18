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
     * A random key of URL-safe characters, for anything that is handed out once and stored hashed.
     *
     * @param positive-int $length
     */
    public static function generate(int $length = 32): string
    {
        return substr(rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '='), 0, $length);
    }
}
