<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Closure;
use Hirtz\Skeleton\Widgets\Icon;

trait IconTrait
{
    protected Closure|string|null $icon = null;

    /**
     * @param Closure(Icon):(Icon|null)|string|null $icon
     * @return $this
     */
    public function icon(Closure|string|null $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    protected function getIcon(): ?Icon
    {
        $icon = Icon::make();

        if ($this->icon instanceof Closure) {
            return ($this->icon)($icon);
        }

        return $this->icon ? $icon->name($this->icon) : null;
    }
}
