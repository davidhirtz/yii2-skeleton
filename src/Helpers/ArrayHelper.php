<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Closure;
use Override;
use yii\helpers\BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
    /**
     * Yii answers a plain `array`, which loses the element type at every call site. Grouping nests the result,
     * so only the ungrouped form keeps it.
     *
     * @template T
     * @param array<T>|null $array
     * @param Closure(T): (int|string|null)|string|null $key
     * @param array<Closure(T): (int|string|null)|string> $groups
     * @return ($groups is array{} ? array<array-key, T> : array<array-key, mixed>)
     */
    #[Override]
    public static function index($array, $key, $groups = []): array
    {
        return parent::index($array, $key, $groups);
    }

    /**
     * @param array<array-key, mixed> $array
     * @noinspection PhpUnused
     */
    public static function replaceValue(array &$array, string $value, mixed $replacement): void
    {
        if (($key = array_search($value, $array, true)) !== false) {
            $array[$key] = $replacement;
        }
    }

    /**
     * @param array<array-key, mixed> $array
     */
    public static function setDefaultValue(array &$array, int|string $key, mixed $value): void
    {
        if (!static::keyExists($key, $array)) {
            $array[$key] = $value;
        }
    }

    /**
     * @param array<array-key, mixed> $array
     * @param array<array-key, mixed> $values
     * @noinspection PhpUnused
     */
    public static function setDefaultValues(array &$array, array $values): void
    {
        foreach ($values as $key => $value) {
            static::setDefaultValue($array, $key, $value);
        }
    }
}
