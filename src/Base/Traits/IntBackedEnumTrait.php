<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base\Traits;

use BackedEnum;
use yii\base\InvalidConfigException;

/**
 * PHP has no `IntBackedEnum` to type against, so a string backed one would only fail on the assignment — with a
 * `TypeError` naming neither the enum nor the class that was handed it.
 */
trait IntBackedEnumTrait
{
    protected static function getIntValue(int|BackedEnum $value): int
    {
        if (!$value instanceof BackedEnum) {
            return $value;
        }

        if (!is_int($value->value)) {
            throw new InvalidConfigException(static::class . ' cannot be declared with ' . $value::class . ', which is backed by a string.');
        }

        return $value->value;
    }
}
