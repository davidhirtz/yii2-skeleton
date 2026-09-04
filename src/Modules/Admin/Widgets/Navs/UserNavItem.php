<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;

class UserNavItem extends NavItem
{
    public function __construct(array $config = [])
    {
        $this->label ??= Lang::t('skeleton', 'COMMON_USERS');
        $this->icon ??= 'users';
        $this->order ??= 100;
        $this->url ??= ['/admin/user/index'];

        $this->roles([
            User::AUTH_USER_CREATE,
            User::AUTH_USER_UPDATE,
        ]);

        $this->routes([
            'admin/user/',
            'admin/user-auth/',
            'admin/user-trail/',
            'admin/user-login/view',
        ]);

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
        return $this->addItem($this->getPermissionItem(), $this->getLoginItem());
    }

    protected function getPermissionItem(): NavItem
    {
        return NavItem::make()
            //->icon('balance-scale-right')
            ->label(Lang::t('skeleton', 'COMMON_PERMISSIONS'))
            ->url(['/admin/auth/index'])
            ->order(20)
            ->roles([User::AUTH_USER_ASSIGN])
            ->routes(['admin/auth']);
    }

    protected function getLoginItem(): NavItem
    {
        return NavItem::make()
            //->icon('sign-in-alt')
            ->label(Lang::t('skeleton', 'COMMON_LOGINS'))
            ->url(['/admin/user-login/index'])
            ->order(40)
            ->roles([User::AUTH_USER_UPDATE])
            ->routes(['admin/user-login/index']);
    }
}
