<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;

class AccountHeader extends Header
{
    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->title ??= $this->model->getUsername();
        $this->url ??= ['/admin/account/update'];

        $this->addContent($this->getAccountActionDropdown());

        parent::configure();
    }

    protected function getAccountActionDropdown(): ?Stringable
    {
        return AccountActionDropdown::make()
            ->model($this->model);
    }
}
