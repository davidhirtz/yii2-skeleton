<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\TimezoneSelectField;
use davidhirtz\yii2\skeleton\widgets\forms\traits\UserActiveFormTrait;
use Stringable;
use yii\widgets\ActiveField;

/**
 * @property UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

    protected function renderContent(): string|Stringable
    {
        $this->rows ??= [
            [
                $this->getStatusField(),
                $this->getNameField(),
                $this->getEmailField(),
                $this->getNewPasswordField(),
                $this->getRepeatPasswordField(),
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
            [
                'sendEmail',
            ],
        ];

        return parent::renderContent();
    }

    protected function getStatusField(): string|Stringable
    {
        return SelectField::make()
            ->model($this->model->user)
            ->property('status');
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
            ->model($this->model)
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
            ->property('newPassword')
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


    public function sendEmailField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'sendEmail')->checkbox($options);
    }

    protected function isNewRecord(): bool
    {
        return $this->model->user->getIsNewRecord();
    }
}
