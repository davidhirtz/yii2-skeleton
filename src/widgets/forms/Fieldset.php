<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\models\interfaces\I18nAttributeInterface;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use davidhirtz\yii2\skeleton\validators\HexColorValidator;
use davidhirtz\yii2\skeleton\validators\SensitiveAttributeValidator;
use davidhirtz\yii2\skeleton\widgets\forms\fields\Field;
use davidhirtz\yii2\skeleton\widgets\forms\fields\HexColorField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\validators\BooleanValidator;
use yii\validators\EmailValidator;

class Fieldset extends Widget
{
    use FormWidgetTrait;
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
                Yii::debug("Skipping invisible attribute '$field->property'");
                continue;
            }

            if (!$field->isSafe()) {
                Yii::debug("Skipping unsafe attribute '$field->property'");
                continue;
            }

            if (!$this->model instanceof I18nAttributeInterface) {
                $rows[] = $field->form($this->form);
                continue;
            }

            foreach ($this->model->getI18nAttributeNames($field->property) as $property) {
                $rows[] = (clone $field)
                    ->property($property)
                    ->form($this->form);
            }
        }

        return \davidhirtz\yii2\skeleton\html\Fieldset::make()
            ->attributes($this->attributes)
            ->addClass('fieldset')
            ->content(...$rows);
    }

    protected function getFieldForProperty(string $property): Field
    {
        $validators = $this->model->getActiveValidators($property);
        $className = InputField::class;
        $type = null;

        foreach ($validators as $validator) {
            if ($validator instanceof SensitiveAttributeValidator) {
                $type = 'password';
                break;
            }

            if ($validator instanceof DynamicRangeValidator) {
                $className = SelectField::make();
                break;
            }

            if($validator instanceof BooleanValidator) {
                // Todo true and false values
                $type = 'checkbox';
                break;
            }

            if ($validator instanceof EmailValidator) {
                $type = 'email';
                break;
            }

            if ($validator instanceof HexColorValidator) {
                $className = HexColorField::class;
                break;
            }
        }

        return $className::make()
            ->type($type)
            ->property($property);
    }
}
