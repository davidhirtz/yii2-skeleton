<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class UserNavItem extends NavItem
{
    public function __construct(array $config = [])
    {
        $this->label ??= Lang::t('skeleton', 'USER_NAV_ITEM_USER_MANAGEMENT');
        $this->icon ??= 'users';
        $this->order ??= 100;
        $this->url ??= ['/admin/user/index'];

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->addDefaultItems();
        parent::configure();
    }

    protected function addDefaultItems(): static
    {
        return $this->addItem($this->getUserItem(), $this->getPermissionItem(), $this->getLoginItem());
    }

    protected function getUserItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('user-friends')
            ->label(Lang::t('skeleton', 'COMMON_USERS'))
            ->url(['/admin/user/index'])
            ->roles([User::AUTH_USER_CREATE, User::AUTH_USER_UPDATE])
            ->routes(['admin/user/', 'admin/user-auth/', 'admin/user-trail/', 'admin/user-login/view']);
    }

    protected function getPermissionItem(): NavItem
    {
        return NavItem::make()
            ->icon('balance-scale-right')
            ->label(Lang::t('skeleton', 'COMMON_PERMISSIONS'))
            ->url(['/admin/auth/index'])
            ->roles([User::AUTH_USER_ASSIGN])
            ->routes(['admin/auth']);
    }

    protected function getLoginItem(): NavItem
    {
        return NavItem::make()
            ->icon('sign-in-alt')
            ->label(Lang::t('skeleton', 'COMMON_LOGINS'))
            ->url(['/admin/user-login/index'])
            ->roles([User::AUTH_USER_UPDATE])
            ->routes(['admin/user-login/index']);
    }
}
