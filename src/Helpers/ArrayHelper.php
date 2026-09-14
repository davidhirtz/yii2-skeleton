<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use yii\helpers\BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
    /**
     * @noinspection PhpUnused
     */
    public static function replaceValue(array &$array, string $value, mixed $replacement): void
    {
        if (($key = array_search($value, $array, true)) !== false) {
            $array[$key] = $replacement;
        }
    }

    public static function setDefaultValue(array &$array, int|string $key, mixed $value): void
    {
        if (!static::keyExists($key, $array)) {
            $array[$key] = $value;
        }
    }

    /**
     * @noinspection PhpUnused
     */
    public static function setDefaultValues(array &$array, array $values): void
    {
        foreach ($values as $key => $value) {
            static::setDefaultValue($array, $key, $value);
        }
    }
}
