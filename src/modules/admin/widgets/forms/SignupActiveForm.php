<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\assets\SignupAssetBundle;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\forms\SignupForm;
use davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\LoginActiveFormTrait;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\CheckboxField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Stringable;
use Yii;
use yii\helpers\Url;

/**
 * @property SignupForm $model
 */
class SignupActiveForm extends ActiveForm
{
    use LoginActiveFormTrait;

    public array $attributes = ['class' => 'form-plain'];
    public bool $hasStickyButtons = false;
    public string $layout = "{errors}{rows}{buttons}";
    public array $excludedErrorProperties = ['name', 'email', 'password'];

    #[\Override]
    protected function renderContent(): string|Stringable
    {
        $this->registerSignupClientScript();

        $this->attributes['id'] ??= 'signup-form';
        $this->attributes['data-id'] = 'signup';

        $this->rows ??= [
            $this->getUsernameField(),
            $this->getEmailField(),
            $this->getPasswordField('new-password'),
            $this->getTermsField(),
            $this->getHoneypotField(),
            $this->getTokenField(),
            $this->getTimezoneField(),
        ];

        $this->submitButtonText ??= Yii::t('skeleton', 'Create Account');

        return parent::renderContent();
    }

    protected function getUsernameField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('name')
            ->autocomplete('username')
            ->autofocus(!$this->model->hasErrors())
            ->placeholder()
            ->prepend(Icon::make()
                ->name('user-circle'));
    }

    protected function getTermsField(): ?Stringable
    {
        return CheckboxField::make()
            ->model($this->model)
            ->property('terms');
    }

    protected function getHoneypotField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('honeypot')
            ->attribute('data-id', 'honeypot')
            ->type('hidden');
    }

    /**
     * @see AccountController::actionToken()
     */
    protected function getTokenField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('token')
            ->attribute('data-id', 'token')
            ->attribute('data-url', Url::toRoute(['account/token']))
            ->type('hidden');
    }

    protected function getTimezoneField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('timezone')
            ->attribute('data-id', 'tz')
            ->type('hidden');
    }

    protected function registerSignupClientScript(): void
    {
        $this->view->registerAssetBundle(SignupAssetBundle::class);
    }
}
