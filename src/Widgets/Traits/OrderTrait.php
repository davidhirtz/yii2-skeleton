<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Widgets\Attributes\Configure;

trait OrderTrait
{
    #[Configure('addOrderStyle')]
    protected ?int $order = null;

    public function order(?int $order): static
    {
        $this->order = $order;
        return $this;
    }

    protected function addOrderStyle(): static
    {
        return $this->order !== null
            ? $this->addStyle(['order' => $this->order])
            : $this;
    }
}
