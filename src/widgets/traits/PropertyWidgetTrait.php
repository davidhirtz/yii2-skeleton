<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\traits;

trait PropertyWidgetTrait
{
    public ?string $property = null;

    public function property(?string $property): static
    {
        $this->property = $property;
        return $this;
    }
}
