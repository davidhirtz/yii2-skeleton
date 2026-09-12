<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Traits;

use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use yii\base\Model;

/**
 * For an {@see \Hirtz\Skeleton\Widgets\Forms\ActiveForm} whose model is a {@see CustomAttributeInterface}.
 */
trait CustomAttributeFieldsTrait
{
    /**
     * @param (Model&CustomAttributeInterface)|null $model the record behind the form, when the form's own model is
     * a wrapper around it
     * @return list<Field> one field per visible definition, in definition order
     */
    public function getCustomAttributeFields((Model&CustomAttributeInterface)|null $model = null): array
    {
        $model ??= $this->model;
        $fields = [];

        foreach ($model->getCustomAttributeDefinitions() as $definition) {
            if ($definition->isVisible($model)) {
                $fields[] = $definition->createField($model);
            }
        }

        return $fields;
    }
}
