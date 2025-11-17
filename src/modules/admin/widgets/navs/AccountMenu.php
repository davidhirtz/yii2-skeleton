<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Dropdown;
use davidhirtz\yii2\skeleton\html\DropdownLink;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\web\User;
use davidhirtz\yii2\skeleton\widgets\navs\Nav;
use davidhirtz\yii2\skeleton\widgets\navs\NavItem;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\helpers\Url;

class AccountMenu extends Widget
{
    public array $attributes = [
        'id' => 'account-menu',
        'class' => 'navbar-nav navbar-right nav',
    ];

    /**
     * @var array|null containing the route of the language dropdown. If not set, the current URL will be used.
     */
    public ?array $languageRoute = null;

    protected User $user;

    public function init(): void
    {
        $this->user ??= Yii::$app->getUser();
        parent::init();
    }

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
                ->text($label)
                ->icon("flag:$language");

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
        return !$this->user->getIsGuest()
            ? NavItem::make()
                ->label($this->user->getIdentity()->getUsername())
                ->url(['/admin/account/update'])
                ->icon('user')
            : null;
    }

    /**
     * @see AccountController::actionLogin()
     */
    protected function getLoginItem(): ?NavItem
    {
        return $this->user->getIsGuest() && $this->user->isLoginEnabled()
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Login'))
                ->url($this->user->loginUrl)
                ->icon('sign-in-alt')
            : null;
    }

    /**
     * @see AccountController::actionLogout()
     */
    protected function getLogoutItem(): ?NavItem
    {
        return !$this->user->getIsGuest()
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
        return $this->user->getIsGuest() && $this->user->isSignupEnabled()
            ? NavItem::make()
                ->label(Yii::t('skeleton', 'Sign up'))
                ->url(['/admin/account/create'])
                ->icon('plus-circle')
            : null;
    }
}
