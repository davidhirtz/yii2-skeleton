<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\traits;

use davidhirtz\yii2\skeleton\widgets\grids\GridView;

trait GridTrait
{
    protected GridView $grid;

    public function grid(GridView $grid): static
    {
        $this->grid = $grid;
        return $this;
    }
}
