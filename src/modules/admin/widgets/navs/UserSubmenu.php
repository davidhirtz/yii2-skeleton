<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\navs\NavItem;
use davidhirtz\yii2\skeleton\widgets\navs\Submenu;
use davidhirtz\yii2\skeleton\widgets\traits\UserWidgetTrait;
use Override;
use Yii;

class UserSubmenu extends Submenu
{
    use UserWidgetTrait;

    #[Override]
    protected function renderContent(): string
    {
        if ($this->user && !$this->user->getIsNewRecord()) {
            $this->title ??= $this->user->getUsername();
            $this->url ??= $this->user->getAdminRoute();
            $this->items = $this->getUserItems();
        } else {
            $this->title ??= Yii::t('skeleton', 'Users');
            $this->url ??= ['/admin/user/index'];
            $this->items = $this->getDefaultItems();
        }

        return parent::renderContent();
    }

    protected function getDefaultItems(): array
    {
        return [
            $this->getUserIndex(),
            $this->getPermissionIndex(),
            $this->getLoginIndex(),
        ];
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

    protected function getUserIndex(): ?NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'Users'))
            ->url(['/admin/user/index'])
            ->icon('users')
            ->routes(['user/(index|owner|create)']);
    }

    protected function getPermissionIndex(): ?NavItem
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN)
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Permissions'))
                ->url(['/admin/auth/index'])
                ->icon('unlock-alt')
                ->routes(['auth/(index|view)'])
            : null;
    }

    protected function getLoginIndex(): ?NavItem
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE)
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Logins'))
                ->url(['/admin/user-login/index'])
                ->icon('bars')
                ->routes(['user-login/(index|view)'])
            : null;
    }

    protected function getUserForm(): ?NavItem
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->user])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'User'))
                ->url(['/admin/user/update', 'id' => $this->user->id])
                ->icon('user')
            : null;
    }

    protected function getUserPermissionIndex(): ?NavItem
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN, ['user' => $this->user])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Permissions'))
                ->url(['/admin/auth/view', 'user' => $this->user->id])
                ->icon('unlock-alt')
            : null;
    }

    protected function getUserLoginIndex(): ?NavItem
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->user])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Logins'))
                ->url(['/admin/user-login/view', 'user' => $this->user->id])
                ->icon('bars')
            : null;
    }

    protected function getUserTrailIndex(): ?NavItem
    {
        return Yii::$app->getUser()->can(Trail::AUTH_TRAIL_INDEX)
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'History'))
                ->url(['/admin/trail/index', 'user' => $this->user->id])
                ->icon('history')
            : null;
    }
}
