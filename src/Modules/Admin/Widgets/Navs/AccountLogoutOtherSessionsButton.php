<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\ConfirmButton;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

/**
 * @see AccountController::actionLogoutOtherSessions()
 */
class AccountLogoutOtherSessionsButton extends ConfirmButton
{
    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'right-from-bracket';
        $this->label ??= Yii::t('skeleton', 'ACCOUNT_LOGOUT_OTHER_SESSIONS');
        $this->style ??= 'danger';
        $this->url ??= ['/admin/account/logout-other-sessions'];

        $this->addContent(P::make()->text(Yii::t('skeleton', 'ACCOUNT_CONFIRM_LOGOUT_OTHER_SESSIONS')));

        parent::configure();
    }

    #[Override]
    protected function getButton(): Button
    {
        return parent::getButton()->addClass('text-nowrap');
    }
}
