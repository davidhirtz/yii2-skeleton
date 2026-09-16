<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits\LoginActiveFormTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Override;
use Stringable;
use Yii;

/**
 * @property LoginForm $model
 */
class LoginActiveForm extends ActiveForm
{
    use LoginActiveFormTrait;

    /**
     * @var array<string, mixed>
     */
    public array $attributes = ['class' => 'form-plain'];
    /**
     * @var list<string>
     */
    public array $excludedErrorProperties = ['email', 'password'];
    public bool $hasStickyButtons = false;
    protected string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        // Ensure the two-factor authentication form is displayed correctly
        $this->attributes['hx-select'] ??= '#wrap';
        $this->attributes['id'] ??= 'login-form';

        $this->submitButtonText ??= Yii::t('skeleton', 'COMMON_LOGIN');

        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            $this->getEmailField(),
            $this->getPasswordField('current-password'),
            $this->getRememberMeField(),
        ];
    }

    protected function getRememberMeField(): ?Stringable
    {
        return $this->webuser->enableAutoLogin
            ? CheckboxField::make()
                ->model($this->model)
                ->property('rememberMe')
            : null;
    }
}
