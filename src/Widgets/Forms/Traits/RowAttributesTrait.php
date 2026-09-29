<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Traits;

trait RowAttributesTrait
{
    /**
     * @var array<string, mixed>
     */
    protected array $rowAttributes = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function addRowAttributes(array $attributes): static
    {
        $this->rowAttributes = [...$this->rowAttributes, ...$attributes];
        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function rowAttributes(array $attributes): static
    {
        $this->rowAttributes = $attributes;
        return $this;
    }
}
