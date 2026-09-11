<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Closure;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;
use Stringable;
use yii\base\Model;

class SelectCustomAttribute extends CustomAttribute
{
    /**
     * @var array<int|string, string>|Closure(Model):array<int|string, string>
     */
    protected array|Closure $options = [];
    protected bool $multiple = false;

    /**
     * @param array<int|string, string>|Closure(Model):array<int|string, string> $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @return array<int|string, string>
     */
    public function getOptions(Model $owner): array
    {
        return $this->options instanceof Closure ? ($this->options)($owner) : $this->options;
    }

    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [
            [
                'in',
                'range' => array_keys($this->getOptions($owner)),
                'allowArray' => $this->multiple,
            ],
        ];
    }

    #[Override]
    public function normalize(mixed $value): mixed
    {
        if (!$this->multiple) {
            return $value === null || $value === '' ? null : $this->normalizeValue($value);
        }

        $values = array_values(array_filter(
            is_array($value) ? $value : [$value],
            static fn (mixed $item): bool => $item !== null && $item !== '',
        ));

        return $values ? array_map($this->normalizeValue(...), $values) : null;
    }

    #[Override]
    public function formatValue(Model $owner, mixed $value): string|Stringable|array|null
    {
        if ($value === null) {
            return null;
        }

        $options = $this->getOptions($owner);
        $labels = array_map(
            static fn (mixed $item): string => (string)($options[$item] ?? $item),
            is_array($value) ? $value : [$value],
        );

        return implode(', ', $labels);
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        $field = SelectField::make()
            ->items($this->getOptions($owner))
            ->multiple($this->multiple);

        if (!$this->isRequired($owner)) {
            $field->prompt('');
        }

        return $this->configureField($field, $owner);
    }

    #[Override]
    protected function getFingerprintData(): array
    {
        return [
            $this->options instanceof Closure ? Closure::class : array_keys($this->options),
            $this->multiple,
        ];
    }

    protected function normalizeValue(mixed $value): int|string
    {
        return is_numeric($value) && (string)(int)$value === (string)$value ? (int)$value : (string)$value;
    }
}
