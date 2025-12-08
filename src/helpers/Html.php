<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\helpers;

use Override;
use yii\base\Model;
use yii\helpers\BaseHtml;

class Html extends BaseHtml
{
    private static int $counter = 0;

    public static function getId(): string
    {
        return 'i' . ++self::$counter;
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

    public static function markKeywords(string $text, array|string|null $keywords, bool $wordBoundary = false): string
    {
        if ($keywords) {
            foreach ((array)$keywords as $keyword) {
                $keyword = preg_quote((string)$keyword);

                if ($wordBoundary) {
                    $keyword = "\b$keyword";
                }

                $text = preg_replace("#($keyword)#ui", '<mark>$1</mark>', (string)$text);
            }
        }

        return $text;
    }
}
