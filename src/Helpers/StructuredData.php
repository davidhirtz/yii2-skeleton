<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use DateTimeInterface;
use DateTimeZone;
use Hirtz\Skeleton\Db\Date;
use Yii;
use yii\helpers\Json;

class StructuredData
{
    final public const string CONTEXT = 'https://schema.org';

    public static function id(string $url, string $fragment): string
    {
        return explode('#', $url, 2)[0] . '#' . $fragment;
    }

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
     * @return array<string, mixed>|null
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
     * An empty list stays: it can be a value (`itemListElement`).
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
     */
    public static function encode(array $nodes): string
    {
        $data = count($nodes) === 1
            ? ['@context' => self::CONTEXT, ...$nodes[0]]
            : ['@context' => self::CONTEXT, '@graph' => $nodes];

        return Json::htmlEncode($data);
    }
}
