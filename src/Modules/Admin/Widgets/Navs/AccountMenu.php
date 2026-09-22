<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class AccountMenu extends Widget
{
    use TagAttributesTrait;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'account-menu';
        $this->attributes['class'] ??= 'aside-nav nav';

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Nav::make()
            ->attributes($this->attributes)
            ->items($this->getItems());
    }

    /**
     * @return array<NavItem|null>
     */
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
                ->routes(['admin/account/'])
                ->icon('user')
            : null;
    }

    /**
     * @see AccountController::actionLogout()
     */
    protected function getLogoutItem(): ?NavItem
    {
        // The icon and the label are passed as content rather than through `icon()` and `text()`, which would
        // wrap them in an `.icon-text` of their own: the markup has to match every other nav link, or the aside
        // has no label to hide while it is collapsed to its icons.
        return !$this->webuser->getIsGuest()
            ? NavItem::make()
                ->content(Button::make()
                    ->content(
                        Icon::make()->name('sign-out-alt')->addClass('nav-link-icon'),
                        Span::make()->class('nav-link-label')->text(Yii::t('skeleton', 'ACCOUNT_MENU_LOGOUT')),
                    )
                    ->modal($this->getLogoutModal())
                    ->class('nav-link nav-logout-link'))
            : null;
    }

    protected function getLogoutModal(): Modal
    {
        $button = Button::make()
            ->danger()
            ->text(Yii::t('skeleton', 'ACCOUNT_MENU_LOGOUT'))
            ->addAttributes([
                'hx-post' => Url::toRoute(['/admin/account/logout']),
                'hx-push-url' => 'true',
                'hx-target' => 'body',
            ]);

        return Modal::make()
            ->title(Yii::t('skeleton', 'ACCOUNT_MENU_LOGOUT'))
            ->content(P::make()->text(Yii::t('skeleton', 'ACCOUNT_CONFIRM_LOGOUT')))
            ->footer($button);
    }
}
