<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Credentials\Credentials;
use Hirtz\Skeleton\Mcp\Mcp;
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
            user: $this->getUserUpdateItem(),
            permissions: $this->getUserPermissionItem(),
            logins: $this->getUserLoginItem(),
            grants: $this->getUserGrantItem(),
            tokens: $this->getUserMcpTokenItem(),
            credentials: $this->getUserCredentialItem(),
            trail: $this->getUserTrailItem(),
        );

        parent::configure();
    }

    protected function getUserUpdateItem(): ?NavItem
    {
        return $this->webuser->can(User::AUTH_USER)
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'COMMON_ACCOUNT'))
                ->url(['/admin/user/update', 'id' => $this->model->id])
                ->icon('user')
            : null;
    }

    protected function getUserPermissionItem(): ?NavItem
    {
        return $this->webuser->can(User::AUTH_USER_ASSIGN, ['user' => $this->model])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'COMMON_PERMISSIONS'))
                ->url(['/admin/user-auth/index', 'id' => $this->model->id])
                ->icon('unlock-alt')
            : null;
    }

    protected function getUserLoginItem(): ?NavItem
    {
        return $this->webuser->can(User::AUTH_USER, ['user' => $this->model])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'COMMON_USER_LOGINS'))
                ->icon('bars')
                ->url(['/admin/user-login/view', 'user' => $this->model->id])
                ->routes(['admin/user-login/view'])
            : null;
    }

    protected function getUserGrantItem(): ?NavItem
    {
        return Mcp::getComponent()->isOAuthEnabled() && $this->webuser->can(User::AUTH_USER, ['user' => $this->model])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'GRANT_CONNECTED_APPS'))
                ->icon('plug')
                ->url(['/admin/grant/view', 'user' => $this->model->id])
                ->routes(['admin/grant/view'])
            : null;
    }

    protected function getUserMcpTokenItem(): ?NavItem
    {
        return Mcp::getComponent()->enableHttp && $this->webuser->can(User::AUTH_USER, ['user' => $this->model])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'MCP_TOKENS_TITLE'))
                ->icon('key')
                ->url(['/admin/mcp-token/view', 'user' => $this->model->id])
                ->routes(['admin/mcp-token/view'])
            : null;
    }

    protected function getUserCredentialItem(): ?NavItem
    {
        return Credentials::getComponent()->getProviders() && $this->webuser->can(User::AUTH_USER, ['user' => $this->model])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'CREDENTIALS_TITLE'))
                ->icon('lock')
                ->url(['/admin/credential/view', 'user' => $this->model->id])
                ->routes(['admin/credential/view'])
            : null;
    }

    protected function getUserTrailItem(): ?NavItem
    {
        return $this->webuser->can(Trail::AUTH_TRAIL_INDEX, ['user' => $this->model])
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'COMMON_HISTORY'))
                ->icon('history')
                ->url(['/admin/user-trail/index', 'id' => $this->model->id])
                ->routes(['admin/user-trail/index'])
            : null;
    }
}
