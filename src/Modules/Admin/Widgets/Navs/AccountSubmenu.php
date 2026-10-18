<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Controllers\GrantController;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Override;
use Yii;

class AccountSubmenu extends Submenu
{
    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            settings: $this->getSettingsItem(),
            logins: $this->getLoginItem(),
            security: $this->getSecurityItem(),
            grants: $this->getGrantItem(),
        );

        parent::configure();
    }

    /**
     * @see AccountController::actionUpdate()
     */
    protected function getSettingsItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'COMMON_SETTINGS'))
            ->url(['/admin/account/update'])
            ->icon('user');
    }

    /**
     * @see AccountController::actionCredentials()
     */
    protected function getLoginItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'COMMON_LOGIN'))
            ->url(['/admin/account/credentials'])
            ->icon('key');
    }

    /**
     * @see AccountController::actionSecurity()
     */
    protected function getSecurityItem(): ?NavItem
    {
        return $this->webuser->enableTwoFactorAuthentication
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'COMMON_SECURITY'))
                ->url(['/admin/account/security'])
                ->icon('shield-halved')
            : null;
    }

    /**
     * @see GrantController::actionIndex()
     */
    protected function getGrantItem(): ?NavItem
    {
        return Mcp::getComponent()->enableHttp
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'GRANT_CONNECTED_APPS'))
                ->url(['/admin/grant/index'])
                ->icon('plug')
            : null;
    }
}
