<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Validators\HexColorValidator;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\HexColorField;
use Override;
use yii\base\Model;

class HexColorCustomAttribute extends CustomAttribute
{
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [['trim'], [HexColorValidator::class]];
    }

    #[Override]
    public function normalize(mixed $value): mixed
    {
        return $value === null || $value === '' ? null : (string)$value;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        $field = HexColorField::make()->attribute('value', $owner->{$this->name} ?? '');
        return $this->configureField($field, $owner);
    }
}
