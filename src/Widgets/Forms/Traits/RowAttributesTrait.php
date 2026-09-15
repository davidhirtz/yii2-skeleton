<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Traits;

trait RowAttributesTrait
{
    /**
     * @var array<string, mixed>
     */
    public array $rowAttributes = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function rowAttributes(array $attributes): static
    {
        $this->rowAttributes = $attributes;
        return $this;
    }
}
