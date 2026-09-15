<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Widgets\Grids\GridView;
use yii\base\Model;

trait GridTrait
{
    /**
     * @var GridView<covariant Model>
     */
    protected GridView $grid;

    /**
     * @param GridView<covariant Model> $grid
     */
    public function grid(GridView $grid): static
    {
        $this->grid = $grid;
        return $this;
    }
}
