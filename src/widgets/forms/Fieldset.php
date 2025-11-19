<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\widgets\forms\fields\Field;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Fieldset extends Widget
{
    use FormTrait;
    use TagAttributesTrait;
    use TagIdTrait;
    use ModelWidgetTrait;

    /**
     * @var Stringable[]|string[]
     */
    protected array $rows = [];

    public function fields(Stringable|string ...$fields): static
    {
        $this->rows = $fields;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        foreach ($this->rows as $i => &$field) {
            if (is_string($field)) {
                $field = Field::make()
                    ->property($field);
            }

            if ($field instanceof Field) {
                $field->form($this->form);

                if (!$field->isVisible()) {
                    unset($this->rows[$i]);
                }
            }
        }

        return \davidhirtz\yii2\skeleton\html\Fieldset::make()
            ->attributes($this->attributes)
            ->addClass('fieldset')
            ->content(...$this->rows);
    }
}