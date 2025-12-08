<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

trait TagCollapsedTrait
{
    protected ?bool $collapsed = null;

    public function collapsed(?bool $collapsed): static
    {
        $this->collapsed = $collapsed;
        return $this;
    }
}
