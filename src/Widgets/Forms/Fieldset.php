<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Closure;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\VisibleAttributeInterface;
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
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\validators\BooleanValidator;
use yii\validators\EmailValidator;
use yii\validators\NumberValidator;
use yii\base\Model;

class Fieldset extends Widget
{
    use FormWidgetTrait;
    use TagAttributesTrait;
    use TagIdTrait;
    /**
     * @use ModelTrait<Model|null>
     */
    use ModelTrait;

    /**
     * @var list<Field|Stringable|string>
     */
    protected array $rows = [];

    /**
     * The closure form is what an outside listener uses to insert a field of its own: the collection is not
     * public, so it is handed the current one and returns the one it wants.
     *
     * @param array<Field|Stringable|string|null>|Closure(list<Field|Stringable|string>): array<Field|Stringable|string|null> $rows
     */
    public function rows(array|Closure $rows): static
    {
        $rows = $rows instanceof Closure ? $rows($this->rows) : $rows;

        $this->rows = array_values(array_filter(
            $rows,
            static fn (Field|Stringable|string|null $row): bool => null !== $row && '' !== $row,
        ));

        return $this;
    }

    /**
     * Before `configure()` these are the rows as declared — a property name, a field or any other stringable.
     * Afterwards they are the resolved fields, cloned per language where the model translates them.
     *
     * @return list<Field|Stringable|string>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    #[Override]
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

            // Before the i18n clones, which carry the language suffix a type's hidden field list does not name.
            if ($field->property && $this->isAttributeHidden($field->property)) {
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
            // Whether a field is visible is decided by its own render, once it has configured itself; whether the
            // model accepts its attribute has to be answered before that, since configuring reads the attribute.
            // A disabled field is unsafe by definition and still renders.
            if ($field instanceof Field && !$field->isSafe() && !$field->isDisabled()) {
                Yii::debug("Skipping field for unsafe attribute '$field->property'");
                unset($rows[$key]);
            }
        }

        $this->rows = array_values($rows);

        parent::configure();
    }

    /**
     * A type declaring a field hidden takes it out of `rules()` as well, so a custom attribute is already gone by
     * here; this is what answers for a column of the model's own.
     */
    protected function isAttributeHidden(string $property): bool
    {
        return $this->model instanceof VisibleAttributeInterface && !$this->model->isAttributeVisible($property);
    }

    protected function getFieldForProperty(string $property): Field
    {
        $definition = $this->model instanceof CustomAttributeInterface
            ? $this->model->getCustomAttribute($property)
            : null;

        if ($definition) {
            return $definition->createField($this->model);
        }

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
                $className = SelectField::class;
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

        $field = $className::make()
            ->property($property)
            ->model($this->model);

        return $field instanceof InputField ? $field->type($type) : $field;
    }

    protected function renderContent(): string|Stringable
    {
        $rows = array_filter(array_map(static fn (string|Stringable $row): string => (string)$row, $this->rows));

        return $rows
            ? \Hirtz\Skeleton\Html\Fieldset::make()
                ->attributes($this->attributes)
                ->addClass('fieldset')
                ->content(...$rows)
            : '';
    }
}
