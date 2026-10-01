<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Yii;
use yii\helpers\BaseStringHelper;

class StringHelper extends BaseStringHelper
{
    /**
     * "DE", "DE and FR", "DE, FR and PT" — the conjunction in the application's language.
     *
     * @param list<string> $items
     */
    public static function enumerate(array $items): string
    {
        $last = array_pop($items);

        return $items
            ? Yii::t('skeleton', 'COMMON_ENUMERATION', ['items' => implode(', ', $items), 'last' => $last])
            : (string)$last;
    }

    public static function humanizeFilename(string $filename): string
    {
        return static::mb_ucwords(str_replace(['.', '_', '-'], ' ', (pathinfo($filename, PATHINFO_FILENAME))));
    }

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
        $textLength = mb_strlen($text);
        $length = min($length, $textLength);

        return mb_substr($text, 0, $length) . str_repeat($replacement, min($textLength - $length, $maxLength));
    }
}
