<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Icon;
use Override;
use Stringable;
use Yii;

/**
 * @property LoginForm $model
 */
class TwoFactorAuthenticationLoginActiveForm extends ActiveForm
{
    public bool $warnOnLeave = false;
    /**
     * @var array<string, mixed>
     */
    public array $attributes = ['class' => 'form-plain'];
    /**
     * @var list<string>
     */
    public array $excludedErrorProperties = ['code'];
    public bool $hasStickyButtons = false;
    protected string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'authentication-form';

        $this->submitButtonText ??= Yii::t('skeleton', 'COMMON_LOGIN');

        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        // The code alone: the account and the remember-me choice wait in the session (`LoginForm::PENDING_SESSION_KEY`).
        return [
            $this->getCodeField(),
        ];
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
}
