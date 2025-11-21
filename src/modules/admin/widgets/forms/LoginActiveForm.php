<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\LoginActiveFormTrait;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\CheckboxField;
use Stringable;
use Yii;

/**
 * @property LoginForm $model
 */
class LoginActiveForm extends ActiveForm
{
    use LoginActiveFormTrait;

    public array $attributes = ['class' => 'form-plain'];
    public array $excludedErrorProperties = ['email', 'password'];
    public bool $hasStickyButtons = false;
    public string $layout = "{errors}{rows}{buttons}";

    protected function renderContent(): string|Stringable
    {
        // Ensure the two-factor authentication form is displayed correctly
        $this->attributes['hx-select'] ??= '#wrap';

        $this->rows ??= [
            $this->getEmailField(),
            $this->getPasswordField('current-password'),
            $this->getRememberMeField(),
        ];

        $this->submitButtonText ??= Yii::t('skeleton', 'Login');

        return parent::renderContent();
    }

    protected function getRememberMeField(): ?Stringable
    {
        return Yii::$app->getUser()->enableAutoLogin
            ? CheckboxField::make()
                ->model($this->model)
                ->property('rememberMe')
            : null;
    }
}
