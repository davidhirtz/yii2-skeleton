<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

trait TagIdTrait
{
    use TagAttributesTrait;

    private static int $counter = 0;

    final public function getId(): string
    {
        return $this->attributes['id'] ??= 'i' . ++self::$counter;
    }
}
