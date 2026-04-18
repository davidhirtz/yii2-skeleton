<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\UserDeleteButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;

class UserActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem($this->getUserDeleteButton(), $this->getOwnerLinkButton());
        parent::configure();
    }

    protected function getUserDeleteButton(): ?Stringable
    {
        return UserDeleteButton::make()
            ->model($this->model);
    }

    protected function getOwnerLinkButton(): ?Stringable
    {
        return UserOwnerButton::make()
            ->model($this->model);
    }
}
