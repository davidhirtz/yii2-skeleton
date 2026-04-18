<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeIconColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Traits\UserWidgetTrait;
use Hirtz\Skeleton\Widgets\Username;
use Override;
use Stringable;

/**
 * @extends GridView<UserLogin>
 */
class UserLoginGridView extends GridView
{
    use UserWidgetTrait;

    #[Override]
    protected function configure(): void
    {
        $this->columns ??= [
            $this->getTypeColumn(),
            $this->getIpAddressColumn(),
            $this->getUserColumn(),
            $this->getBrowserColumn(),
            $this->getCreatedAtColumn(),
        ];

        parent::configure();
    }

    protected function getTypeColumn(): ?Column
    {
        return TypeIconColumn::make();
    }

    protected function getIpAddressColumn(): ?Column
    {
        return LinkColumn::make()
            ->property('ip_address')
            ->content(fn (UserLogin $login): string => $login->getDisplayIp())
            ->url(fn (UserLogin $login) => ['view', 'id' => $login->id]);
    }

    protected function getUserColumn(): ?Column
    {
        return LinkColumn::make()
            ->property('user')
            ->visible(!$this->user)
            ->content(fn (UserLogin $login): Stringable => Username::make()->user($login->user))
            ->url(fn (UserLogin $login): array => ['view', 'user' => $login->user_id]);
    }

    protected function getBrowserColumn(): ?Column
    {
        return DataColumn::make()
            ->property('browser')
            ->hiddenForSmallDevices();
    }

    protected function getCreatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('created_at')
            ->hiddenForSmallDevices();
    }
}
