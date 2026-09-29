<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

trait OrderTrait
{
    protected ?int $order = null;

    public function order(?int $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }
}
