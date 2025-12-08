<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids\traits;

use Hirtz\Skeleton\widgets\grids\GridView;

trait GridTrait
{
    protected GridView $grid;

    public function grid(GridView $grid): static
    {
        $this->grid = $grid;
        return $this;
    }
}
