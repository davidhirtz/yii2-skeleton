<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
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

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function isRequired(Model $owner): bool
    {
        return $this->required instanceof Closure ? (bool)($this->required)($owner) : $this->required;
    }

    public function isVisible(Model $owner): bool
    {
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

    public function formatValue(Model $owner, mixed $value): string|Stringable|null
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string)$value : Json::encode($value);
    }

    abstract public function createField(Model $owner): Field;

    /**
     * Hashes what the server renders, so a form only has to reload when a type change actually changes its shape.
     */
    final public function getFingerprint(): string
    {
        return md5(serialize([
            static::class,
            $this->name,
            $this->translatable,
            $this->label,
            $this->hint,
            ...$this->getFingerprintData(),
        ]));
    }

    /**
     * @return list<mixed> a closure has no stable hash and must be represented by an opaque marker
     */
    protected function getFingerprintData(): array
    {
        return [];
    }

    /**
     * Label and hint are left to the model: it reports them through `attributeLabels()` and `attributeHints()`, which
     * is what gives the per-language clone of a translatable definition its own "(DE)" label.
     */
    protected function configureField(Field $field, Model $owner): Field
    {
        $field->model($owner)
            ->property($this->name);

        if ($this->isRequired($owner)) {
            $field->attribute('required', true);
        }

        if ($this->isDisabled($owner)) {
            $field->attribute('disabled', true);
        }

        return $field;
    }
}
