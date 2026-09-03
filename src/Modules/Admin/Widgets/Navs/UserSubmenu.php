<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class UserSubmenu extends Submenu
{
    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getUserUpdateItem(),
            $this->getUserPermissionItem(),
            $this->getUserLoginItem(),
            $this->getUserTrailItem(),
        );

        parent::configure();
    }

    protected function getUserUpdateItem(): ?NavItem
    {
        return $this->webuser->can(User::AUTH_USER_UPDATE, ['user' => $this->model])
            ? NavItem::make()
                ->label(Lang::t('skeleton', 'COMMON_ACCOUNT'))
                ->url(['/admin/user/update', 'id' => $this->model->id])
                ->icon('user')
            : null;
    }

    protected function getUserPermissionItem(): ?NavItem
    {
        return $this->webuser->can(User::AUTH_USER_ASSIGN, ['user' => $this->model])
            ? NavItem::make()
                ->label(Lang::t('skeleton', 'COMMON_PERMISSIONS'))
                ->url(['/admin/user-auth/index', 'id' => $this->model->id])
                ->icon('unlock-alt')
            : null;
    }

    protected function getUserLoginItem(): ?NavItem
    {
        return $this->webuser->can(User::AUTH_USER_UPDATE, ['user' => $this->model])
            ? NavItem::make()
                ->label(Lang::t('skeleton', 'COMMON_LOGINS'))
                ->icon('bars')
                ->url(['/admin/user-login/view', 'user' => $this->model->id])
                ->routes(['admin/user-login/view'])
            : null;
    }

    protected function getUserTrailItem(): ?NavItem
    {
        return $this->webuser->can(Trail::AUTH_TRAIL_INDEX)
            ? NavItem::make()
                ->label(Lang::t('skeleton', 'COMMON_HISTORY'))
                ->icon('history')
                ->url(['/admin/user-trail/index', 'id' => $this->model->id])
                ->routes(['admin/user-trail/index'])
            : null;
    }
}
