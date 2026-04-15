<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

trait PropertyTrait
{
    public ?string $property = null;

    public function property(?string $property): static
    {
        $this->property = $property;
        return $this;
    }
}
