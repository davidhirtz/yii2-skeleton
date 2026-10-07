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
 * @see UserController::actionDisableAuthenticator()
 */
class UserDisableAuthenticatorButton extends ConfirmButton
{
    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    #[Override]
    public function isVisible(): bool
    {
        return $this->model->hasTwoFactorAuthentication()
            && $this->webuser->enableTwoFactorAuthentication
            && $this->webuser->can(User::AUTH_USER, ['user' => $this->model]);
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'shield-halved';
        $this->label ??= Yii::t('skeleton', 'USER_DISABLE_AUTHENTICATOR_LABEL');
        $this->style ??= 'danger';
        $this->url ??= ['/admin/user/disable-authenticator', 'id' => $this->model->id];

        $this->addContent(P::make()->text(Yii::t('skeleton', 'USER_CONFIRM_DISABLE_AUTHENTICATOR')));

        parent::configure();
    }
}
