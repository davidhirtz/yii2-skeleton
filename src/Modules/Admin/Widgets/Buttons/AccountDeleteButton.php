<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Yii;

/**
 * @see AccountController::actionDelete()
 *
 * @extends DeleteButton<User>
 */
class AccountDeleteButton extends DeleteButton
{
    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && $this->model->isDeletable();
    }

    #[Override]
    protected function configure(): void
    {
        $this->url ??= ['/admin/account/delete'];
        $this->property ??= 'password';
        $this->message ??= Yii::t('skeleton', 'ACCOUNT_DELETE_TYPE_PASSWORD');
        $this->title ??= Yii::t('skeleton', 'ACCOUNT_CONFIRM_DELETE');

        parent::configure();
    }

    /**
     * The password is verified, never compared, so it must not reach the markup as the input's `pattern`.
     */
    #[Override]
    protected function getInput(): TextInput
    {
        return TextInput::make()
            ->autofocus()
            ->autocomplete('off')
            ->class('input')
            ->name('value')
            ->placeholder($this->model->getAttributeLabel($this->property))
            ->required()
            ->type('password');
    }
}
