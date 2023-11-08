<?php

namespace davidhirtz\yii2\skeleton\helpers;

use yii\helpers\BaseStringHelper;

class StringHelper extends BaseStringHelper
{
    public static function humanizeFilename(string $filename): string
    {
        return static::mb_ucfirst(str_replace(['.', '_', '-'], ' ', (pathinfo($filename, PATHINFO_FILENAME))));
    }

    /**
     * @noinspection PhpUnused
     */
    public static function obfuscateEmail(
        string $email,
        bool $obfuscateDomain = true,
        int $length = 2,
        int $maxLength = 5,
        string $replacement = '*'
    ): string {
        $parts = explode('@', $email);
        $url = explode('.', $parts[1]);

        $email = static::obfuscateText($parts[0], $length, $maxLength, $replacement) . '@';

        if ($obfuscateDomain) {
            $email .= static::obfuscateText(array_shift($url), $length, $maxLength, $replacement) . '.';
        }

        return $email . implode('.', $url);
    }

    public static function obfuscateText(
        string $text,
        int $length = 2,
        int $maxLength = 5,
        string $replacement = '*'
    ): string {
        $length = min($length, strlen($text));
        return substr($text, 0, $length) . str_repeat($replacement, min(strlen($text) - $length, $maxLength));
    }
}
