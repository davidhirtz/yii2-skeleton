<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use yii\helpers\BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
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
