<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Stringable;
use Yii;

/**
 * @property User $model
 */
class UserDeleteButton extends DeleteButton
{
    public function isVisible(): bool
    {
        return parent::isVisible() && $this->model->isDeletable();
    }

    #[Override]
    protected function configure(): void
    {
        $this->property ??= 'email';
        $this->title ??= Yii::t('skeleton', 'Are you sure you want to delete this user?');

        $this->addContent($this->getMessage());

        parent::configure();
    }

    protected function getMessage(): ?Stringable
    {
        return P::make()->text(Yii::t('skeleton', 'Please type the user email in the text field below to delete this user. All related records and files will also be deleted. This cannot be undone, please be certain!'));
    }
}
