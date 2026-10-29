<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Traits;

use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use yii\base\Model;

trait CustomAttributeFieldsTrait
{
    /**
     * `$model` is the record behind the form when the form's own model wraps it.
     *
     * @param (Model&CustomAttributeInterface)|null $model
     * @param list<string> $except
     * @return list<Field>
     */
    public function getCustomAttributeFields(
        (Model&CustomAttributeInterface)|null $model = null,
        array $except = []
    ): array {
        $model ??= $this->model instanceof CustomAttributeInterface ? $this->model : null;

        if ($model === null) {
            return [];
        }

        $fields = [];

        foreach ($model->getCustomAttributeDefinitions() as $definition) {
            if (!in_array($definition->name, $except, true) && $definition->isVisible($model)) {
                $fields[] = $definition->getField($model);
            }
        }

        return $fields;
    }
}
