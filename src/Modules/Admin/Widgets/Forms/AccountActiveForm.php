<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits\UserActiveFormTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Override;

/**
 * @see AccountController::actionUpdate()
 *
 * @property AccountUpdateForm $model
 */
class AccountActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            [
                $this->getNameField(),
            ],
            [
                $this->getLanguageField(),
                $this->getTimezoneField(),
                $this->getColorSchemeField(),
            ],
            [
                $this->getShowHintsField(),
            ],
            [
                ...$this->getUserCustomAttributeFields(),
            ],
        ];
    }
}
