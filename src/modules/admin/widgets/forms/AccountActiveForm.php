<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\AccountUpdateForm;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\traits\UserActiveFormTrait;
use Stringable;
use Yii;

/**
 * @property AccountUpdateForm $model
 */
class AccountActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

    protected function renderContent(): string|Stringable
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

        return parent::renderContent();
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

    /**
     * @todo
     */
    protected function getOldPasswordFieldIcon(array $options = []): string
    {
        return Html::icon('info-circle')
            ->tooltip(Yii::t('skeleton', 'Only needed, if you want to change your password'))
            ->addAttributes($options)
            ->render();
    }
}
