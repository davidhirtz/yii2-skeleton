<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use yii\base\Model;

trait ModelWidgetTrait
{
    public ?Model $model = null;

    public function model(?Model $model): static
    {
        $this->model = $model;
        return $this;
    }
}
