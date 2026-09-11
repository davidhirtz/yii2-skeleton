<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

class BooleanCustomAttribute extends CustomAttribute
{
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [['boolean']];
    }

    #[Override]
    public function normalize(mixed $value): mixed
    {
        return $value === null || $value === '' ? null : (bool)$value;
    }

    #[Override]
    public function formatValue(Model $owner, mixed $value): string|Stringable|array|null
    {
        return $value === null ? null : Yii::t('yii', $value ? 'Yes' : 'No');
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(CheckboxField::make()->uncheckedValue('0'), $owner);
    }
}
