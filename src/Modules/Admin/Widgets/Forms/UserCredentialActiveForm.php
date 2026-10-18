<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\UserCredentialForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\CredentialController;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;
use Stringable;
use Yii;

/**
 * @see CredentialController::actionCreate()
 *
 * @property UserCredentialForm $model
 */
class UserCredentialActiveForm extends ActiveForm
{
    #[Override]
    protected function configure(): void
    {
        $this->submitButtonText ??= Yii::t('skeleton', 'CREDENTIAL_BUTTON_SAVE');
        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            [
                $this->getProviderField(),
                $this->getSecretField(),
            ],
        ];
    }

    protected function getProviderField(): string|Stringable
    {
        return SelectField::make()
            ->property('provider')
            ->items($this->model->getProviderItems());
    }

    protected function getSecretField(): string|Stringable
    {
        return InputField::make()
            ->property('secret')
            ->type('password')
            ->attribute('autocomplete', 'off');
    }
}
