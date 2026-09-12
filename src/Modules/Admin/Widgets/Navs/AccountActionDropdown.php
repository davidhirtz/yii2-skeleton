<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\AccountDeleteButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;

class AccountActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem($this->getAccountDeleteButton());
        parent::configure();
    }

    protected function getAccountDeleteButton(): ?Stringable
    {
        return AccountDeleteButton::make()
            ->model($this->model);
    }
}
