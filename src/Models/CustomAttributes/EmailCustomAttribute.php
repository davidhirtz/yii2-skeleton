<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use yii\base\Model;

class EmailCustomAttribute extends TextCustomAttribute
{
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [
            ...parent::getValidationRules($owner),
            ['email'],
        ];
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(InputField::make()->type('email'), $owner);
    }
}
