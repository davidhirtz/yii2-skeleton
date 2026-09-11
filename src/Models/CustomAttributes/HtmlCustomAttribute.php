<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Validators\HtmlValidator;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
use Override;
use Yii;
use yii\base\Model;

class HtmlCustomAttribute extends CustomAttribute
{
    protected array|string|null $validator = HtmlValidator::class;
    protected ?int $max = 65535;

    private ?HtmlValidator $_validator = null;

    public function validator(array|string|null $validator): static
    {
        $this->validator = $validator;
        $this->_validator = null;

        return $this;
    }

    public function max(?int $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function getValidator(): ?HtmlValidator
    {
        if ($this->validator === null) {
            return null;
        }

        /** @var HtmlValidator $validator */
        $validator = $this->_validator ??= Yii::createObject($this->validator);

        return $validator;
    }

    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        $rules = [['trim']];

        if ($this->validator !== null) {
            $rules[] = (array)$this->validator;
        }

        if ($this->max !== null) {
            $rules[] = ['string', 'max' => $this->max];
        }

        return $rules;
    }

    #[Override]
    public function normalize(mixed $value): mixed
    {
        return $value === null || $value === '' ? null : (string)$value;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(TinyMceField::make()->validator($this->validator), $owner);
    }

    #[Override]
    protected function getFingerprintData(): array
    {
        return [$this->validator, $this->max];
    }
}
