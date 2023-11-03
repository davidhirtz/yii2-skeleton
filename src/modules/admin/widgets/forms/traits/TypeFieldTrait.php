<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveField;

trait TypeFieldTrait
{
    public function typeField(array $options = []): ActiveField|string
    {
        return count($types = $this->getTypes()) > 1
            ? $this->field($this->model, 'type', $options)->dropDownList($types)
            : '';
    }

    protected function getTypes(): array
    {
        return ArrayHelper::getColumn($this->model::getTypes(), 'name');
    }

    public function getTypeToggleOptions(): array
    {
        $toggle = [];

        foreach ($this->model::getTypes() as $type => $typeOptions) {
            if ($hidden = ($typeOptions['hiddenFields'] ?? false)) {
                $toggle[] = [$type, $hidden];
            }
        }

        return $this->getToggleOptions($toggle);
    }
}