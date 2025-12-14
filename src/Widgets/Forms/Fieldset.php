<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Validators\HexColorValidator;
use Hirtz\Skeleton\Validators\HtmlValidator;
use Hirtz\Skeleton\Validators\SensitiveAttributeValidator;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\HexColorField;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
use Hirtz\Skeleton\Widgets\Forms\Traits\FormWidgetTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
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

    public function rows(array $rows): static
    {
        $this->rows = array_filter($rows);
        return $this;
    }

    #[\Override]
    protected function configure(): void
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

            $field->form($this->form);

            if (!$this->model instanceof I18nAttributeInterface || !$field->property) {
                $rows[] = $field;
                continue;
            }

            foreach ($this->model->getI18nAttributeNames($field->property) as $language => $property) {
                $rows[] = (clone $field)
                    ->language($language)
                    ->property($property);
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

        $this->rows = array_values($rows);

        parent::configure();
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
                    ->property($property)
                    ->model($this->model);
            }

            if ($validator instanceof EmailValidator) {
                $type = 'email';
                break;
            }

            if ($validator instanceof HtmlValidator) {
                return TinyMceField::make()
                    ->property($property)
                    ->model($this->model)
                    ->validator($validator);
            }

            if ($validator instanceof HexColorValidator) {
                $className = HexColorField::class;
                break;
            }
        }

        return $className::make()
            ->type($type)
            ->property($property)
            ->model($this->model);
    }

    protected function renderContent(): string|Stringable
    {
        return $this->rows
            ? \Hirtz\Skeleton\Html\Fieldset::make()
                ->attributes($this->attributes)
                ->addClass('fieldset')
                ->content(...$this->rows)
            : '';
    }
}
