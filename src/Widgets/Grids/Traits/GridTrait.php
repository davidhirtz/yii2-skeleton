<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Widgets\Grids\GridView;

/**
 * @template T of GridView
 */
trait GridTrait
{
    /**
     * @var T
     */
    protected GridView $grid;

    /**
     * @param T $grid
     */
    public function grid(GridView $grid): static
    {
        $this->grid = $grid;
        return $this;
    }
}
