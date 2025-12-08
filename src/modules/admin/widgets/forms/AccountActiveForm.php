<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\forms;

use Hirtz\Skeleton\models\forms\AccountUpdateForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\traits\UserActiveFormTrait;
use Hirtz\Skeleton\widgets\forms\ActiveForm;
use Hirtz\Skeleton\widgets\forms\fields\InputField;
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
