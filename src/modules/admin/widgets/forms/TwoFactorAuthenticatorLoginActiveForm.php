<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Override;
use Stringable;
use Yii;

/**
 * @property LoginForm $model
 */
class TwoFactorAuthenticatorLoginActiveForm extends ActiveForm
{
    public array $attributes = ['class' => 'form-plain'];
    public array $excludedErrorProperties = ['code'];
    public bool $hasStickyButtons = false;
    public string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            $this->getCodeField(),
            $this->getEmailField(),
            $this->getPasswordField(),
            $this->getRememberMeField(),
        ];

        $this->submitButtonText ??= Yii::t('skeleton', 'Login');

        parent::configure();
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
