<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use yii\base\Model;

class UrlCustomAttribute extends TextCustomAttribute
{
    protected ?string $defaultScheme = 'https';

    public function defaultScheme(?string $defaultScheme): static
    {
        $this->defaultScheme = $defaultScheme;
        return $this;
    }

    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [
            ...parent::getValidationRules($owner),
            ['url', 'defaultScheme' => $this->defaultScheme],
        ];
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(InputField::make()->type('url'), $owner);
    }

    #[Override]
    protected function getFingerprintData(): array
    {
        return [...parent::getFingerprintData(), $this->defaultScheme];
    }
}
