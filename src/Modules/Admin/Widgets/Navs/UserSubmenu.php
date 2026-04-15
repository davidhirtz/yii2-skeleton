<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\UserActiveDataProvider;
use Hirtz\Skeleton\Widgets\Attributes\Configure;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Yii;

class UserSubmenu extends Submenu
{
    /**
     * @use ModelTrait<User|null>
     */
    use ModelTrait;

    /**
     * @use ProviderTrait<UserActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Configure]
    protected function setDefaults(): void
    {

    }

    #[Override]
    protected function renderContent(): string
    {
        if ($this->user && !$this->user->getIsNewRecord()) {
            $this->title ??= $this->user->getUsername();
            $this->url ??= $this->user->getAdminRoute();
            $this->items = $this->getUserItems();
        } else {
            $this->title ??= Yii::t('skeleton', 'Users');

            $this->view->title($this->title);

            $this->title = Yii::t('skeleton', 'Users');
            $this->url ??= ['/admin/user/index'];

            $this->items = $this->getDefaultItems();
        }

        $this->view->addBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

        return parent::renderContent();
    }

    protected function getDefaultItems(): array
    {
        return [];
    }

    protected function getUserItems(): array
    {
        return [
            $this->getUserForm(),
            $this->getUserPermissionIndex(),
            $this->getUserLoginIndex(),
            $this->getUserTrailIndex(),
        ];
    }

    protected function getUserForm(): ?NavItem
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->user])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Account'))
                ->url(['/admin/user/update', 'id' => $this->user->id])
                ->icon('user')
            : null;
    }

    protected function getUserPermissionIndex(): ?NavItem
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN, ['user' => $this->user])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Permissions'))
                ->url(['/admin/user-auth/index', 'id' => $this->user->id])
                ->icon('unlock-alt')
            : null;
    }

    protected function getUserLoginIndex(): ?NavItem
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->user])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Logins'))
                ->icon('bars')
                ->url(['/admin/user-login/view', 'user' => $this->user->id])
                ->routes(['admin/user-login/view'])
            : null;
    }

    protected function getUserTrailIndex(): ?NavItem
    {
        return Yii::$app->getUser()->can(Trail::AUTH_TRAIL_INDEX)
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'History'))
                ->icon('history')
                ->url(['/admin/user-trail/index', 'id' => $this->user->id])
                ->routes(['admin/user-trail/index'])
            : null;
    }
}
