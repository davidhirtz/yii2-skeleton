<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\traits;

use yii\db\ActiveRecord;

trait ModelWidgetTrait
{
    protected ?ActiveRecord $model = null;

    public function model(?ActiveRecord $model): static
    {
        $this->model = $model;
        return $this;
    }

}
