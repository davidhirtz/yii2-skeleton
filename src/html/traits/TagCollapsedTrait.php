<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\traits;

trait TagCollapsedTrait
{
    protected ?bool $collapsed = null;

    public function collapsed(?bool $collapsed): static
    {
        $this->collapsed = $collapsed;
        return $this;
    }
}
