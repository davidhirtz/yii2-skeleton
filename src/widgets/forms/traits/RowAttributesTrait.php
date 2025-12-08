<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms\traits;

trait RowAttributesTrait
{
    public array $rowAttributes = [];

    public function rowAttributes(array $attributes): static
    {
        $this->rowAttributes = $attributes;
        return $this;
    }
}
