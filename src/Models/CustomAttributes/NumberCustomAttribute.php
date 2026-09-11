<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use yii\base\Model;

class NumberCustomAttribute extends CustomAttribute
{
    protected bool $integerOnly = true;
    protected int|float|null $min = null;
    protected int|float|null $max = null;

    public function integerOnly(bool $integerOnly = true): static
    {
        $this->integerOnly = $integerOnly;
        return $this;
    }

    public function min(int|float|null $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(int|float|null $max): static
    {
        $this->max = $max;
        return $this;
    }

    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        $rule = ['number', 'integerOnly' => $this->integerOnly];

        if ($this->min !== null) {
            $rule['min'] = $this->min;
        }

        if ($this->max !== null) {
            $rule['max'] = $this->max;
        }

        return [$rule];
    }

    #[Override]
    public function normalize(mixed $value): mixed
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return $this->integerOnly ? (int)$value : (float)$value;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        $field = InputField::make()->type('number');

        if (!$this->integerOnly) {
            $field->attribute('step', 'any');
        }

        return $this->configureField($field, $owner);
    }

    #[Override]
    protected function getFingerprintData(): array
    {
        return [$this->integerOnly, $this->min, $this->max];
    }
}
