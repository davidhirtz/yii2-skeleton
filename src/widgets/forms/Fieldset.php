<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\models\interfaces\I18nAttributeInterface;
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
        $rows = [];

        foreach ($this->rows as $field) {
            if (is_string($field)) {
                $field = $this->getFieldForProperty($field);
            }

            if (!$field instanceof Field) {
                $rows[] = $field;
                continue;
            }

            if (!$field->isVisible()) {
                continue;
            }

            if ($this->model instanceof I18nAttributeInterface) {
                foreach ($this->model->getI18nAttributeNames($field->property) as $property) {
                    $rows[] = (clone $field)
                        ->property($property)
                        ->form($this->form);
                }
            } else {
                $rows[] = $field->form($this->form);
            }
        }

        return \davidhirtz\yii2\skeleton\html\Fieldset::make()
            ->attributes($this->attributes)
            ->addClass('fieldset')
            ->content(...$rows);
    }

    protected function getFieldForProperty(string $property): Field
    {
        return Field::make()
            ->property($property);
    }
}