<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Yii;

/**
 * @extends DeleteButton<User>
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
        $this->message ??= Yii::t('skeleton', 'USER_DELETE_TYPE_USER');
        $this->property ??= 'email';
        $this->title ??= Yii::t('skeleton', 'USER_CONFIRM_DELETE');

        parent::configure();
    }
}
