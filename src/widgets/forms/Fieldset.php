<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\models\interfaces\I18nAttributeInterface;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use davidhirtz\yii2\skeleton\validators\HexColorValidator;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use davidhirtz\yii2\skeleton\validators\SensitiveAttributeValidator;
use davidhirtz\yii2\skeleton\widgets\forms\fields\CheckboxField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\Field;
use davidhirtz\yii2\skeleton\widgets\forms\fields\HexColorField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\TinyMceField;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\validators\BooleanValidator;
use yii\validators\EmailValidator;
use yii\validators\NumberValidator;

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

    public function rows(Stringable|string|null ...$rows): static
    {
        $this->rows = array_filter($rows);
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

        foreach ($rows as $key => $field) {
            if (!$field instanceof Field) {
                continue;
            }

            if (!$field->isVisible()) {
                Yii::debug("Skipping field for invisible attribute '$field->property'");
                unset($rows[$key]);
                continue;
            }

            if (!$field->isSafe()) {
                Yii::debug("Skipping field for unsafe attribute '$field->property'");
                unset($rows[$key]);
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

            if ($validator instanceof NumberValidator) {
                $type = 'number';
                break;
            }

            if ($validator instanceof DynamicRangeValidator) {
                $className = SelectField::make();
                break;
            }

            if ($validator instanceof BooleanValidator) {
                return CheckboxField::make()
                    ->uncheckedValue('0' !== $validator->falseValue ? $validator->falseValue : null)
                    ->property($property);
            }

            if ($validator instanceof EmailValidator) {
                $type = 'email';
                break;
            }

            if ($validator instanceof HtmlValidator) {
                return TinyMceField::make()
                    ->property($property)
                    ->validator($validator);
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
