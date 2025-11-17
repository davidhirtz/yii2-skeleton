<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\traits;

use yii\base\Model;

trait ModelWidgetTrait
{
    protected ?Model $model = null;

    public function model(?Model $model): static
    {
        $this->model = $model;
        return $this;
    }
}
