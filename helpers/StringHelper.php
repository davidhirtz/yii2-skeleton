<?php

namespace davidhirtz\yii2\skeleton\helpers;

use yii\helpers\BaseStringHelper;

/**
 * Class StringHelper
 * @package davidhirtz\yii2\skeleton\helpers
 */
class StringHelper extends BaseStringHelper
{
    /**
     * Obfuscates email address for public display.
     *
     * @param string $email
     * @param bool $obfuscateDomain
     * @param int $length
     * @param int $maxLength
     * @param string $replacement
     * @return string
     */
    public static function obfuscateEmail($email, $obfuscateDomain = true, $length = 2, $maxLength = 5, $replacement = '*')
    {
        $parts = explode('@', $email);
        $url = explode('.', $parts[1]);

        $email = static::obfuscateText($parts[0], $length, $maxLength, $replacement) . '@';

        if ($obfuscateDomain) {
            $email .= static::obfuscateText(array_shift($url), $length, $maxLength, $replacement) . '.';
        }

        return $email . implode('.', $url);
    }

    /**
     * @param string $text
     * @param int $length
     * @param int $maxLength
     * @param string $replacement
     * @return string
     */
    public static function obfuscateText($text, $length = 2, $maxLength = 5, $replacement = '*')
    {
        $length = min($length, strlen($text));
        return substr($text, 0, $length) . str_repeat($replacement, min(strlen($text) - $length, $maxLength));
    }
}