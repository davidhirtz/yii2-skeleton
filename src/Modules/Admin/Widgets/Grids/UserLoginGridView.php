<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Modules\Admin\Controllers\UserLoginController;
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

    /**
     * `LinkColumn` wraps the cell's *value*, so a `content()` of its own would replace the link instead of filling it.
     * @see UserLoginController::actionIndex()
     */
    protected function getIpAddressColumn(): ?Column
    {
        return LinkColumn::make()
            ->property('ip_address')
            ->value(fn (UserLogin $login): string => $login->getDisplayIp())
            ->url(fn (UserLogin $login): array|false => $login->ip_address
                ? ['index', 'q' => $login->getDisplayIp()]
                : false);
    }

    /**
     * @see UserLoginController::actionView()
     */
    protected function getUserColumn(): ?Column
    {
        return DataColumn::make()
            ->property('user')
            ->visible(!$this->user)
            ->content(function (UserLogin $login): Stringable {
                $username = Username::make()->user($login->user);

                // An administrator is refused the owner's logins, as is anyone holding less than the account does.
                return $login->user && $this->webuser->can(User::AUTH_USER, ['user' => $login->user])
                    ? $username->href(['view', 'user' => $login->user_id])
                    : $username;
            });
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
