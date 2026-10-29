<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\VisibleAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\FormTextField;
use Stringable;
use Yii;
use yii\base\InvalidConfigException;
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
    protected Closure|bool $plainText = false;
    protected mixed $default = null;
    protected ?Closure $field = null;

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

    /**
     * A translatable attribute refuses it.
     */
    public function plainText(Closure|bool $plainText = true): static
    {
        $this->plainText = $plainText;
        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function field(?Closure $field): static
    {
        $this->field = $field;
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

    /**
     * A single content language keeps the bare name, which the source language would otherwise own and leave
     * unwritten.
     */
    public function isTranslatable(): bool
    {
        return $this->translatable && count(Yii::$app->getI18n()->getLanguages()) > 1;
    }

    public function isRequired(Model $owner): bool
    {
        return $this->required instanceof Closure ? (bool)($this->required)($owner) : $this->required;
    }

    /**
     * A type hiding the attribute counts as `visible(false)`: no field, no rule, no assignment.
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

    public function isPlainText(Model $owner): bool
    {
        return $this->plainText instanceof Closure ? (bool)($this->plainText)($owner) : $this->plainText;
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
        return $this->isTranslatable() && $owner instanceof I18nAttributeInterface
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

        if ($this->isDisabled($owner) || $this->isPlainText($owner)) {
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
     * Rule tails without the attribute names, e.g. `[['string', 'max' => 255]]`.
     *
     * @return list<array>
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
     * An array is a label => value map the trail renders as rows of its own.
     *
     * @return string|Stringable|array<string, string|Stringable|null>|null
     */
    public function formatValue(Model $owner, mixed $value): string|Stringable|array|null
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string)$value : Json::encode($value);
    }

    abstract public function createField(Model $owner): Field;

    public function getField(Model $owner): Field
    {
        if (!$this->isPlainText($owner)) {
            return $this->createField($owner);
        }

        if ($this->isTranslatable()) {
            throw new InvalidConfigException("Custom attribute \"$this->name\" cannot be translatable and plain text.");
        }

        $value = $this->formatValue($owner, $owner->{$this->name});

        return $this->configureField(FormTextField::make()->content(is_array($value) ? null : $value), $owner);
    }

    /**
     * Called for every changed attribute before the old values are updated, so a hook may still rewrite it.
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
     * A duplicate is inserted with a reference to what the source owns outside the column; here it gets its own copy.
     *
     * @see \Hirtz\Skeleton\Models\Actions\DuplicateActiveRecord::afterDuplicate()
     */
    public function afterDuplicate(Model $duplicate, Model $source, string $name): void
    {
    }

    /**
     * Label and hint are the model's `attributeLabels()` and `attributeHints()`, which give a translatable clone its
     * "(DE)" label.
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

        if ($this->field !== null) {
            $field->prepare($this->field);
        }

        return $field;
    }
}
