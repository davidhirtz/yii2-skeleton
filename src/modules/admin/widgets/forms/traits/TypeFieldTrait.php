<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveField;

trait TypeFieldTrait
{
    public function typeField(array $options = []): ActiveField|string
    {
        $options['inputOptions'] ??= $this->getTypeToggleOptions();

        return count($types = $this->getTypeItems()) > 1
            ? $this->field($this->model, 'type', $options)->dropDownList($types)
            : '';
    }

    protected function getTypeItems(): array
    {
        $types = array_filter($this->model::getTypes(), $this->filterByTypeOption(...));
        return ArrayHelper::getColumn($types, 'name');
    }

    protected function filterByTypeOption(array $typeOptions): bool
    {
        $visible = $typeOptions['visible'] ?? true;
        return is_callable($visible) ? call_user_func($visible, $this->model) : $visible;
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
