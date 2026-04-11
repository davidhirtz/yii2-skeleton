<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Widgets\Attributes\Configure;

trait StickyTrait
{
    protected bool $sticky = false;

    public function sticky(bool $sticky = true): static
    {
        $this->sticky = $sticky;
        return $this;
    }

    #[Configure]
    protected function addStickyClass(): static
    {
        return $this->sticky ? $this->addClass('sticky') : $this;
    }
}
