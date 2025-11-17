<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\traits;

trait PropertyWidgetTrait
{
    protected ?string $property = null;

    public function property(?string $property): static
    {
        $this->property = $property;
        return $this;
    }
}