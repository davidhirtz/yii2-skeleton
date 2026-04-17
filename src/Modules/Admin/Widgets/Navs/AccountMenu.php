<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Web\User;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class AccountMenu extends Widget
{
    use TagAttributesTrait;

    protected User $webuser;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'account-menu';
        $this->attributes['class'] ??= 'aside-nav nav';

        $this->webuser = Yii::$app->getUser();

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Nav::make()
            ->attributes($this->attributes)
            ->items($this->getItems());
    }

    protected function getItems(): array
    {
        return [
            $this->getAccountItem(),
            $this->getLogoutItem(),
        ];
    }

    /**
     * @see AccountController::actionUpdate()
     */
    protected function getAccountItem(): ?NavItem
    {
        return !$this->webuser->getIsGuest()
            ? NavItem::make()
                ->label($this->webuser->getIdentity()->getUsername())
                ->url(['/admin/account/update'])
                ->icon('user')
            : null;
    }

    /**
     * @see AccountController::actionLogout()
     */
    protected function getLogoutItem(): ?NavItem
    {
        return !$this->webuser->getIsGuest()
            ? NavItem::make()
                ->content(Button::make()
                    ->text(Yii::t('skeleton', 'Logout'))
                    ->addAttributes([
                        'hx-post' => Url::toRoute(['/admin/account/logout']),
                        'hx-push-url' => 'true',
                        'hx-target' => 'body',
                    ])
                    ->icon('sign-out-alt')
                    ->class('nav-link nav-logout-link'))
            : null;
    }
}
