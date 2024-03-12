<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveField;

trait StatusFieldTrait
{
    public function statusField(array $options = []): ActiveField|string
    {
        return count($statuses = $this->getStatusItems()) > 1
            ? $this->field($this->model, 'status', $options)->dropDownList($statuses)
            : '';
    }

    protected function getStatusItems(): array
    {
        return ArrayHelper::getColumn($this->model::getStatuses(), 'name');
    }
}
