<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\LoginActiveFormTrait;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Stringable;

/**
 * @property LoginForm $model
 */
class TwoFactorAuthenticatorLoginActiveForm extends ActiveForm
{
    use LoginActiveFormTrait;

    protected function renderContent(): string|Stringable
    {
        $this->configureForm();

        $this->rows ??= [
            $this->getCodeField(),
            $this->getEmailField(),
            $this->getPasswordField(),
            $this->getRememberMeField(),
        ];

        return parent::renderContent();
    }

    public function getCodeField(): ?Stringable
    {
        return InputField::make()
            ->property('code')
            ->autocomplete('one-time-code')
            ->autofocus()
            ->prepend(Icon::make()
                ->name('qrcode'))
            ->placeholder();
    }

    public function getEmailField(): ?Stringable
    {
        return InputField::make()
            ->property('email')
            ->type('hidden');
    }

    public function getPasswordField(): ?Stringable
    {
        return InputField::make()
            ->property('password')
            ->type('hidden');
    }

    public function getRememberMeField(): ?Stringable
    {
        return InputField::make()
            ->property('rememberMe')
            ->type('hidden');
    }
}
