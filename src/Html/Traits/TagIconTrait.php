<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Closure;
use Hirtz\Skeleton\Widgets\Icon;

trait TagIconTrait
{
    protected ?Icon $icon = null;

    /**
     * @param Icon|Closure(Icon): (Icon|null)|string|null $icon
     * @return $this
     */
    public function icon(Icon|Closure|string|null $icon): static
    {
        $this->icon = is_string($icon)
            ? Icon::make()->name($icon)
            : (is_callable($icon) ? ($icon)($this->icon ?? Icon::make()) : $icon);

        return $this;
    }
}
