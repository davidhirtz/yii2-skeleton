<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits\UserActiveFormTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Stringable;

/**
 * @property AccountUpdateForm $model
 */
class AccountActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

    #[\Override]
    protected function configure(): void
    {
        $this->rows ??= [
            [
                $this->getNameField(),
                $this->getEmailField(),
                $this->getNewPasswordField(),
                $this->getRepeatPasswordField(),
            ],
            [
                $this->getOldPasswordField(),
            ],
            [
                $this->getLanguageField(),
                $this->getTimezoneField(),
            ],
            [
                $this->getFirstNameField(),
                $this->getLastNameField(),
                $this->getCityField(),
                $this->getCountryField(),
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
