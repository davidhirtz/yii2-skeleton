<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Icon;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Web\User;
use Hirtz\Skeleton\Widgets\Navs\Dropdown;
use Hirtz\Skeleton\Widgets\Navs\DropdownLink;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\helpers\Url;

class AccountMenu extends Widget
{
    use TagAttributesTrait;

    protected ?array $languageRoute = null;
    protected User $webuser;

    protected function configure(): void
    {
        $this->attributes['id'] ??= 'account-menu';
        $this->attributes['class'] ??= 'navbar-nav navbar-right nav';

        $this->webuser = Yii::$app->getUser();

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Nav::make()
            ->attributes($this->attributes)
            ->items(...$this->getItems());
    }

    protected function getItems(): array
    {
        return [
            $this->getLanguageDropdownItem(),
            $this->getAccountItem(),
            $this->getLoginItem(),
            $this->getLogoutItem(),
            $this->getSignupItem(),
        ];
    }

    protected function getLanguageDropdownItem(): ?NavItem
    {
        $i18n = Yii::$app->getI18n();

        if (count($i18n->getLanguages()) < 2) {
            return null;
        }

        $dropdown = Dropdown::make()
            ->dropend()
            ->button(Button::make()
                ->class('nav-link')
                ->content(Icon::make()
                    ->name(Yii::$app->language)
                    ->collection(Icon::ICON_COLLECTION_FLAG)));

        foreach ($i18n->getLanguages() as $language) {
            $label = $i18n->getLabel($language);

            $link = DropdownLink::make()
                ->addClass('i18n-dropdown-link')
                ->content(
                    Icon::make()
                        ->collection(Icon::ICON_COLLECTION_FLAG)
                        ->name($language),
                    Div::make()
                        ->addText($label)
                );

            if ($this->languageRoute) {
                $link->href([
                    ...Yii::$app->getRequest()->getQueryParams(),
                    ...$this->languageRoute,
                    'language' => $language,
                ]);
            } else {
                $link->current(['language' => $language]);
            }

            $dropdown->addItem($link);
        }

        return NavItem::make()
            ->content($dropdown);
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
     * @see AccountController::actionLogin()
     */
    protected function getLoginItem(): ?NavItem
    {
        return $this->webuser->getIsGuest() && $this->webuser->isLoginEnabled()
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Login'))
                ->url($this->webuser->loginUrl)
                ->icon('sign-in-alt')
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
                    ->class('nav-link navbar-logout'))
            : null;
    }

    /**
     * @see AccountController::actionCreate()
     */
    protected function getSignupItem(): ?NavItem
    {
        return $this->webuser->getIsGuest() && $this->webuser->isSignupEnabled()
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Sign up'))
                ->url(['/admin/account/create'])
                ->icon('plus-circle')
            : null;
    }
}
