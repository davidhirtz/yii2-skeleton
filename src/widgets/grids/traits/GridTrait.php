<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Widgets\Grids\GridView;

trait GridTrait
{
    protected GridView $grid;

    public function grid(GridView $grid): static
    {
        $this->grid = $grid;
        return $this;
    }
}
