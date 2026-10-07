<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\UserController;
use Hirtz\Skeleton\Widgets\Buttons\ConfirmButton;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

/**
 * @see UserController::actionReset()
 */
class UserPasswordResetButton extends ConfirmButton
{
    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    #[Override]
    public function isVisible(): bool
    {
        return (bool)$this->model->id
            && $this->webuser->isPasswordResetEnabled()
            && $this->webuser->can(User::AUTH_USER, ['user' => $this->model]);
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'key';
        $this->label ??= Yii::t('skeleton', 'USER_PASSWORD_RESET_LABEL');
        $this->url ??= ['/admin/user/reset', 'id' => $this->model->id];

        $this->addContent(P::make()->text(Yii::t('skeleton', 'USER_CONFIRM_PASSWORD_RESET', [
            'email' => $this->model->email,
        ])));

        parent::configure();
    }
}
