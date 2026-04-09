<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class UserNavItem extends NavItem
{
    public function __construct(array $config = [])
    {
        $this->label ??= Yii::t('skeleton', 'Users');
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
        return $this->addItems($this->getUserItem(), $this->getPermissionItem(), $this->getLoginItem());
    }

    protected function getUserItem(): ?NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'Users'))
            ->url(['/admin/user/index'])
            ->roles([User::AUTH_USER_CREATE, User::AUTH_USER_UPDATE])
            ->routes(['admin/user/', 'admin/user-auth/', 'admin/user-trail/', 'admin/user-login/view']);
    }

    protected function getPermissionItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'Permissions'))
            ->url(['/admin/auth/index'])
            ->roles([User::AUTH_USER_ASSIGN])
            ->routes(['admin/auth']);
    }

    protected function getLoginItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'Logins'))
            ->url(['/admin/user-login/index'])
            ->roles([User::AUTH_USER_UPDATE])
            ->routes(['admin/user-login/index']);
    }
}
