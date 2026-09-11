<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TextareaField;
use Override;
use yii\base\Model;

class TextCustomAttribute extends CustomAttribute
{
    protected ?int $min = null;
    protected ?int $max = 255;
    protected bool $multiline = false;

    public function min(?int $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(?int $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function multiline(bool $multiline = true): static
    {
        $this->multiline = $multiline;
        return $this;
    }

    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        $rule = ['string'];

        if ($this->min !== null) {
            $rule['min'] = $this->min;
        }

        if ($this->max !== null) {
            $rule['max'] = $this->max;
        }

        return [['trim'], $rule];
    }

    #[Override]
    public function normalize(mixed $value): mixed
    {
        return $value === null || $value === '' ? null : (string)$value;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        $field = $this->multiline ? TextareaField::make() : InputField::make();
        return $this->configureField($field, $owner);
    }

    #[Override]
    protected function getFingerprintData(): array
    {
        return [$this->min, $this->max, $this->multiline];
    }
}
