<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\UserActiveDataProvider;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\StatusFilterDropdown;
use Hirtz\Skeleton\Widgets\Link;
use Override;
use Stringable;
use Yii;

/**
 * @template T of User
 * @extends GridView<T>
 * @property UserActiveDataProvider $provider
 */
class UserGridView extends GridView
{
    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'user-grid';

        $this->header ??= [
            $this->getStatusDropdown(),
            $this->getSearchInput(),
        ];

        $this->rowAttributes = fn (User $user) => $user->isDisabled() ? ['class' => 'disabled'] : [];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getNameColumn(),
            $this->getEmailColumn(),
            $this->getLastLoginColumn(),
            $this->getCreatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getStatusDropdown(): ?Stringable
    {
        return StatusFilterDropdown::make()
            ->model(User::instance());
    }

    protected function getStatusColumn(): ?Column
    {
        return StatusIconColumn::make();
    }

    protected function getNameColumn(): Column
    {
        return DataColumn::make()
            ->property('name')
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(User $user): Stringable
    {
        $name = ($name = $user->getUsername())
            ? Div::make()
                ->content($this->search->markKeywords($name))
                ->class('strong')
            : Div::make()
                ->content(Lang::t('skeleton', 'COMMON_USER'))
                ->class('text-muted');

        return $this->canUpdateUser($user) ? A::make()->content($name)->href($user->getAdminRoute()) : $name;
    }

    protected function getEmailColumn(): Column
    {
        return DataColumn::make()
            ->property('email')
            ->content($this->getEmailColumnContent(...))
            ->hiddenForSmallDevices();
    }

    protected function getEmailColumnContent(User $user): ?Stringable
    {
        $link = Link::make()
            ->content($this->search->markKeywords($user->email))
            ->mailto($user->email);

        if ($user->isUnconfirmed()) {
            $link->tooltip(Lang::t('skeleton', 'USER_UNCONFIRMED_EMAIL'))
                ->addClass('text-muted');
        }

        return $link;
    }

    protected function getLastLoginColumn(): DataColumn
    {
        return RelativeTimeColumn::make()
            ->property('last_login')
            ->url(fn (User $user) => ['/admin/login/index', 'user' => $user->id]);
    }

    protected function getCreatedAtColumn(): DataColumn
    {
        return RelativeTimeColumn::make()
            ->property('created_at')
            ->hiddenForMediumDevices();
    }

    protected function getButtonColumn(): ButtonColumn
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(User $user): array|string
    {
        if ($this->canUpdateUser($user)) {
            return Button::make()
                ->primary()
                ->url($user->getAdminRoute())
                ->icon('wrench')
                ->render();
        }

        if ($this->webuser->can(User::AUTH_USER_ASSIGN, ['user' => $user])) {
            return Button::make()
                ->primary()
                ->url(['/admin/auth/assign', 'user' => $user->id])
                ->icon('unlock-alt')
                ->tooltip(Lang::t('skeleton', 'COMMON_PERMISSIONS'))
                ->render();
        }

        return [];
    }

    protected function canUpdateUser(User $user): bool
    {
        return $this->webuser->can(User::AUTH_USER_UPDATE, ['user' => $user]);
    }
}
