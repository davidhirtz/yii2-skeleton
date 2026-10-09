<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use DateTimeInterface;
use DateTimeZone;
use Hirtz\Skeleton\Db\Date;
use Yii;
use yii\helpers\Json;

/**
 * What a schema.org node is easy to get wrong in: ids, dates and the encoding of the script.
 */
class StructuredData
{
    final public const string CONTEXT = 'https://schema.org';

    /**
     * An `@id` is the URL of the page that describes the thing plus a fragment naming it, so nodes on one page can
     * reference each other and two pages describing the same thing agree.
     */
    public static function id(string $url, string $fragment): string
    {
        return explode('#', $url, 2)[0] . '#' . $fragment;
    }

    /**
     * A {@see Date} is a day and is written without a time; anything else is ISO 8601 with the offset of the
     * application's time zone, which is what the page shows.
     */
    public static function date(?DateTimeInterface $date): ?string
    {
        return match (true) {
            $date === null => null,
            $date instanceof Date => $date->format('Y-m-d'),
            default => \DateTimeImmutable::createFromInterface($date)
                ->setTimezone(new DateTimeZone(Yii::$app->getTimeZone()))
                ->format(DATE_ATOM),
        };
    }

    /**
     * @return array<string, mixed>|null an `ImageObject`, `null` without a URL
     */
    public static function image(?string $url, ?int $width = null, ?int $height = null): ?array
    {
        return $url ? self::filter([
            '@type' => 'ImageObject',
            'url' => Url::to($url, true),
            'width' => $width,
            'height' => $height,
        ]) : null;
    }

    /**
     * Drops `null` and `''` at every level, so a node can name each property whether or not it has a value. An empty
     * list stays: it can be a value (`itemListElement` of a page without crumbs).
     *
     * @template TKey of array-key
     * @param array<TKey, mixed> $node
     * @return array<TKey, mixed>
     */
    public static function filter(array $node): array
    {
        $filtered = [];

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $value = self::filter($value);
            }

            if ($value !== null && $value !== '') {
                $filtered[$key] = $value;
            }
        }

        return array_is_list($node) ? array_values($filtered) : $filtered;
    }

    /**
     * @param list<array<string, mixed>> $nodes
     * @return string one node on its own, several as a `@graph`, escaped for a `<script>`
     */
    public static function encode(array $nodes): string
    {
        $data = count($nodes) === 1
            ? ['@context' => self::CONTEXT, ...$nodes[0]]
            : ['@context' => self::CONTEXT, '@graph' => $nodes];

        return Json::htmlEncode($data);
    }
}
