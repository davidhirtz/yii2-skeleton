<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Dropdown;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\Nav;
use davidhirtz\yii2\skeleton\html\NavLink;
use davidhirtz\yii2\skeleton\web\User;
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

    public bool $hideSingleItem = false;

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

    protected function renderContent(): Stringable
    {
        return Nav::make()
            ->attributes($this->attributes)
            ->addItems($this->getItems())
            ->hideSingleItem($this->hideSingleItem);
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

    protected function getAccountItem(): ?Tag
    {
        $identity = $this->user->getIdentity();

        if ($identity === null) {
            return null;
        }

        return NavLink::make()
            ->href(['/admin/account/update'])
            ->icon('user')
            ->label($identity->getUsername());
    }

    protected function getLanguageDropdownItem(): ?Tag
    {
        $i18n = Yii::$app->getI18n();

        if (count($i18n->getLanguages()) < 2) {
            return null;
        }

        $dropdown = Dropdown::make()
            ->dropend()
            ->button(Button::make()
                ->class('nav-link')
                ->html(Icon::make()
                    ->name(Yii::$app->language)
                    ->collection(Icon::ICON_COLLECTION_FLAG)));

        foreach ($i18n->getLanguages() as $language) {
            $label = $i18n->getLabel($language);

            $link = A::make()
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

        return $dropdown;
    }

    /**
     * @see AccountController::actionLogin()
     */
    protected function getLoginItem(): ?Tag
    {
        if (!$this->user->getIsGuest() || !$this->user->isLoginEnabled()) {
            return null;
        }

        return NavLink::make()
            ->href($this->user->loginUrl)
            ->icon('sign-in-alt')
            ->label(Yii::t('skeleton', 'Login'));
    }

    /**
     * @see AccountController::actionLogout()
     */
    protected function getLogoutItem(): ?Tag
    {
        if ($this->user->getIsGuest()) {
            return null;
        }

        return NavLink::make()
            ->addAttributes([
                'hx-post' => Url::toRoute(['/admin/account/logout']),
                'hx-push-url' => 'true',
                'hx-target' => 'body',
            ])
            ->addClass('navbar-logout')
            ->icon('sign-out-alt')
            ->label(Yii::t('skeleton', 'Logout'));
    }

    /**
     * @see AccountController::actionCreate()
     */
    protected function getSignupItem(): ?Tag
    {
        if (!$this->user->getIsGuest() || !$this->user->isSignupEnabled()) {
            return null;
        }

        return NavLink::make()
            ->href(['/admin/account/create'])
            ->icon('plus-circle')
            ->label(Yii::t('skeleton', 'Sign up'));
    }
}
