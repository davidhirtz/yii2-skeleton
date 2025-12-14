<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Traits;

trait RowAttributesTrait
{
    public array $rowAttributes = [];

    public function rowAttributes(array $attributes): static
    {
        $this->rowAttributes = $attributes;
        return $this;
    }
}
