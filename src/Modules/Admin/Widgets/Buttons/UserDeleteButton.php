<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Yii;

/**
 * @property User $model
 */
class UserDeleteButton extends DeleteButton
{
    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && $this->model->isDeletable();
    }

    #[Override]
    protected function configure(): void
    {
        $this->message ??= Lang::t('skeleton', 'USER_DELETE_PLEASE_TYPE_THE_USER_EMAIL_IN');
        $this->property ??= 'email';
        $this->title ??= Lang::t('skeleton', 'USER_CONFIRM_DELETE');

        parent::configure();
    }
}
