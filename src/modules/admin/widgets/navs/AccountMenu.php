<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Dropdown;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\NavLink;
use davidhirtz\yii2\skeleton\html\Ul;
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

    public array $itemAttributes = [];

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
        $items = array_filter($this->getItems());

        if (!$items) {
            return '';
        }

        $list = Ul::make()
            ->attributes($this->attributes);

        foreach ($items as $item) {
            $list->addItem($item, $this->itemAttributes);
        }

        return $list;
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
        return !$this->user->getIsGuest()
            ? NavLink::make()
                ->href(['/admin/account/update'])
                ->icon('user')
                ->label($this->user->getIdentity()->getUsername())
            : null;
    }

    protected function getLanguageDropdownItem(): ?Dropdown
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
    protected function getLoginItem(): ?NavLink
    {
        return $this->user->getIsGuest() && $this->user->isLoginEnabled()
            ? NavLink::make()
                ->href($this->user->loginUrl)
                ->icon('sign-in-alt')
                ->label(Yii::t('skeleton', 'Login'))
            : null;
    }

    /**
     * @see AccountController::actionLogout()
     */
    protected function getLogoutItem(): ?NavLink
    {
        return !$this->user->getIsGuest()
            ? NavLink::make()
                ->addAttributes([
                    'hx-post' => Url::toRoute(['/admin/account/logout']),
                    'hx-push-url' => 'true',
                    'hx-target' => 'body',
                ])
                ->addClass('navbar-logout')
                ->icon('sign-out-alt')
                ->label(Yii::t('skeleton', 'Logout'))
            : null;
    }

    /**
     * @see AccountController::actionCreate()
     */
    protected function getSignupItem(): ?NavLink
    {
        return $this->user->getIsGuest() && $this->user->isSignupEnabled()
            ? NavLink::make()
                ->href(['/admin/account/create'])
                ->icon('plus-circle')
                ->label(Yii::t('skeleton', 'Sign up'))
            : null;
    }
}
