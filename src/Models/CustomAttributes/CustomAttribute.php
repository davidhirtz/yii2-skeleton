<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\VisibleAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Stringable;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\helpers\Json;

abstract class CustomAttribute
{
    use ContainerConfigurationTrait;

    protected ?string $label = null;
    protected ?string $hint = null;
    protected ?string $placeholder = null;
    protected bool $translatable = false;
    protected Closure|bool $required = false;
    protected Closure|bool $visible = true;
    protected Closure|bool $disabled = false;
    protected mixed $default = null;

    public function __construct(public readonly string $name)
    {
    }

    public function label(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function hint(?string $hint): static
    {
        $this->hint = $hint;
        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function translatable(bool $translatable = true): static
    {
        $this->translatable = $translatable;
        return $this;
    }

    public function required(Closure|bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function visible(Closure|bool $visible = true): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function disabled(Closure|bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? Inflector::camel2words($this->name);
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function isRequired(Model $owner): bool
    {
        return $this->required instanceof Closure ? (bool)($this->required)($owner) : $this->required;
    }

    /**
     * A type hiding the attribute is the same answer as a `visible(false)` of the definition's own: no field, no
     * rule, and therefore no assignment — {@see \Hirtz\Skeleton\Models\Types\Type::hiddenFields()}.
     */
    public function isVisible(Model $owner): bool
    {
        if ($owner instanceof VisibleAttributeInterface && !$owner->isAttributeVisible($this->name)) {
            return false;
        }

        return $this->visible instanceof Closure ? (bool)($this->visible)($owner) : $this->visible;
    }

    public function isDisabled(Model $owner): bool
    {
        return $this->disabled instanceof Closure ? (bool)($this->disabled)($owner) : $this->disabled;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @return list<string>
     */
    public function getAttributeNames(Model $owner): array
    {
        return $this->translatable && $owner instanceof I18nAttributeInterface
            ? array_values($owner->getI18nAttributeNames($this->name))
            : [$this->name];
    }

    /**
     * @return list<array>
     */
    public function getRules(Model $owner): array
    {
        if (!$this->isVisible($owner)) {
            return [];
        }

        $names = $this->getAttributeNames($owner);

        if ($this->isDisabled($owner)) {
            return [[array_map(static fn (string $name) => "!$name", $names), 'safe']];
        }

        $rules = [];

        foreach ($this->getValidationRules($owner) as $rule) {
            $rules[] = [$names, ...$rule];
        }

        if ($this->required instanceof Closure) {
            $rules[] = [$names, 'required', 'when' => fn (): bool => $this->isRequired($owner)];
        } elseif ($this->required) {
            $rules[] = [$names, 'required'];
        }

        $rules[] = [
            $names,
            'filter',
            'filter' => $this->normalize(...),
            'skipOnArray' => false,
            'skipOnEmpty' => false,
        ];

        return $rules;
    }

    /**
     * @return list<array> rule tails without the attribute names, e.g. `[['string', 'max' => 255]]`
     */
    abstract protected function getValidationRules(Model $owner): array;

    public function normalize(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }

    public function serialize(mixed $value): mixed
    {
        return $this->normalize($value);
    }

    public function unserialize(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @return string|Stringable|array<string, string|Stringable|null>|null an array is a label => value map the trail
     * renders as its own rows
     */
    public function formatValue(Model $owner, mixed $value): string|Stringable|array|null
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string)$value : Json::encode($value);
    }

    abstract public function createField(Model $owner): Field;

    /**
     * Side effects a definition has beyond the JSON column, such as a file to move into place or to remove. Called
     * from {@see \Hirtz\Skeleton\Db\ActiveRecord::afterSave()} for every attribute whose value changed, before the
     * old values are updated, so a hook may still rewrite the attribute.
     */
    public function afterSave(Model $owner, string $name, mixed $old): void
    {
    }

    /**
     * @see \Hirtz\Skeleton\Db\ActiveRecord::afterDelete()
     */
    public function afterDelete(Model $owner, string $name): void
    {
    }

    /**
     * A duplicate is inserted with the source's values, which for a definition storing something outside the column
     * is a reference to what the source owns — this is where it gets its own copy.
     *
     * @see \Hirtz\Skeleton\Models\Actions\DuplicateActiveRecord::afterDuplicate()
     */
    public function afterDuplicate(Model $duplicate, Model $source, string $name): void
    {
    }

    /**
     * Label and hint are left to the model: it reports them through `attributeLabels()` and `attributeHints()`, which
     * is what gives the per-language clone of a translatable definition its own "(DE)" label.
     */
    protected function configureField(Field $field, Model $owner): Field
    {
        $field->model($owner)
            ->property($this->name);

        if ($this->placeholder !== null && method_exists($field, 'placeholder')) {
            $field->placeholder($this->placeholder);
        }

        if ($this->isRequired($owner)) {
            $field->attribute('required', true);
        }

        if ($this->isDisabled($owner)) {
            $field->attribute('disabled', true);
        }

        return $field;
    }
}
