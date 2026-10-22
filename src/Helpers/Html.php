<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Override;
use yii\helpers\BaseHtml;

class Html extends BaseHtml
{
    private static int $counter = 0;

    public static function getId(): string
    {
        return 'i' . ++self::$counter;
    }

    public static function reset(): void
    {
        self::$counter = 0;
    }

    #[Override]
    public static function getInputIdByName($name): string
    {
        return strtr(
            mb_strtolower($name, 'UTF-8'),
            [
                '[]' => '',
                '][' => '-',
                '[' => '-',
                ']' => '',
                ' ' => '-',
                '.' => '-',
                '--' => '-',
                '_' => '-',
            ]
        );
    }

    /**
     * Encodes the text and marks every keyword in one pass, so none matches inside an entity or another's mark.
     *
     * @param list<string>|string|null $keywords
     */
    public static function markKeywords(string $text, array|string|null $keywords, bool $wordBoundary = false): string
    {
        $keywords = array_values(array_filter(array_map(strval(...), (array)$keywords), fn (string $keyword): bool => $keyword !== ''));

        if (!$keywords) {
            return static::encode($text);
        }

        usort($keywords, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $pattern = implode('|', array_map(fn (string $keyword): string => preg_quote($keyword, '#'), $keywords));
        $parts = preg_split('#' . ($wordBoundary ? '\b' : '') . "($pattern)#ui", $text, flags: PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $html = '';

        foreach ($parts as $index => $part) {
            $html .= $index % 2 ? '<mark>' . static::encode($part) . '</mark>' : static::encode($part);
        }

        return $html;
    }
}
