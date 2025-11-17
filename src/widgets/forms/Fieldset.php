<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Fieldset extends Widget
{
    use FormTrait;
    use ModelWidgetTrait;
    use TagAttributesTrait;
    use TagIdTrait;

    /**
     * @var ActiveFieldNew[]|string[]
     */
    protected ?array $fields;

    protected function renderContent(): string|Stringable
    {
        foreach ($this->fields as $i => $field) {
            if (is_string($field)) {
                $field = ActiveFieldNew::make()
                    ->property($field);
            }

            $field->grid($this);

            if (!$field->isVisible()) {
                unset($this->fields[$i]);
            }
        }
        
        // TODO: Implement renderContent() method.
    }
}