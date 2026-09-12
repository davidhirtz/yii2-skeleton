<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
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
            $this->getSettingsItem(),
            $this->getLoginItem(),
            $this->getSecurityItem(),
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
}
