<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\base\traits;

use Yii;

trait ContainerConfigurationTrait
{
    public static function make(...$args): static
    {
        return Yii::createObject(static::class, $args);
    }
}
