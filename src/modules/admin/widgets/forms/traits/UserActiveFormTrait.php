<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\TimezoneSelectField;
use Stringable;

trait UserActiveFormTrait
{
    protected function getStatusField(): string|Stringable
    {
        return SelectField::make()
            ->model($this->model)
            ->property('status')
            ->items(ArrayHelper::getColumn($this->model->user::getStatuses(), 'name'))
            ->visible(!$this->model->user->isOwner());
    }

    protected function getNameField(): string|Stringable
    {
        return InputField::make()
            ->model($this->model->user)
            ->property('name');
    }

    protected function getEmailField(): string|Stringable
    {
        return InputField::make()
            ->model($this->model->user)
            ->property('email')
            ->type('email');
    }

    protected function getNewPasswordField(): string|Stringable
    {
        return InputField::make()
            ->property('newPassword')
            ->type('password');
    }

    protected function getRepeatPasswordField(): string|Stringable
    {
        return InputField::make()
            ->property('repeatPassword')
            ->type('password');
    }

    protected function getLanguageField(): string|Stringable
    {
        return SelectField::make()
            ->model($this->model->user)
            ->property('language');
    }

    protected function getTimezoneField(): string|Stringable
    {
        return TimezoneSelectField::make()
            ->model($this->model->user)
            ->property('timezone');
    }

    protected function getFirstNameField(): string|Stringable
    {
        return InputField::make()
            ->model($this->model->user)
            ->property('first_name');
    }

    protected function getLastNameField(): string|Stringable
    {
        return InputField::make()
            ->model($this->model->user)
            ->property('last_name');
    }

    protected function getCityField(): string|Stringable
    {
        return InputField::make()
            ->model($this->model->user)
            ->property('city');
    }

    protected function getCountryField(): string|Stringable
    {
        return SelectField::make()
            ->model($this->model->user)
            ->property('country');
    }
}
