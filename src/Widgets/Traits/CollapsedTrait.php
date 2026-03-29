<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

trait CollapsedTrait
{
    protected ?bool $collapsed = null;

    public function collapsed(?bool $collapsed): static
    {
        $this->collapsed = $collapsed;
        return $this;
    }
}
