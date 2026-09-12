<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits\UserActiveFormTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use Stringable;

/**
 * @see AccountController::actionCredentials()
 *
 * @property AccountUpdateForm $model
 */
class AccountCredentialsActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            [
                $this->getEmailField(),
            ],
            [
                $this->getNewPasswordField(),
                $this->getRepeatPasswordField(),
            ],
            [
                $this->getOldPasswordField(),
            ],
        ];

        parent::configure();
    }

    protected function getOldPasswordField(): string|Stringable
    {
        if (!$this->model->user->password_hash) {
            return '';
        }

        return InputField::make()
            ->property('oldPassword')
            ->type('password');
    }
}
