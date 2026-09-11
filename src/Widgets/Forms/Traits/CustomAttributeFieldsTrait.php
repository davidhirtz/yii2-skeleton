<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Traits;

use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;

/**
 * For an {@see \Hirtz\Skeleton\Widgets\Forms\ActiveForm} whose model is a {@see CustomAttributeInterface}.
 */
trait CustomAttributeFieldsTrait
{
    /**
     * @return list<Field> one field per visible definition, in definition order
     */
    public function getCustomAttributeFields(): array
    {
        $fields = [];

        foreach ($this->model->getCustomAttributeDefinitions() as $definition) {
            if ($definition->isVisible($this->model)) {
                $fields[] = $definition->createField($this->model);
            }
        }

        return $fields;
    }
}
