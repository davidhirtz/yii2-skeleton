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
 * @see UserController::actionOwnership()
 */
class UserOwnerButton extends ConfirmButton
{
    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    #[Override]
    public function isVisible(): bool
    {
        return $this->webuser->getIdentity()->isOwner() && !$this->model->isOwner();
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'star';
        $this->label ??= Yii::t('skeleton', 'USER_OWNER_BUTTON');
        $this->confirmLabel ??= Yii::t('skeleton', 'USER_OWNER_SUBMIT_BUTTON');
        $this->style ??= 'danger';
        $this->url ??= ['/admin/user/ownership', 'id' => $this->model->id];

        $this->addContent(P::make()->text(Yii::t('skeleton', 'USER_OWNER_CONFIRM')));

        parent::configure();
    }
}
