<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\base\traits;

use Yii;

trait ContainerTrait
{
    public static function make(...$args): static
    {
        return Yii::createObject(static::class, $args);
    }
}
