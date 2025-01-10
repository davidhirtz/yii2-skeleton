<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\Dropdown;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\Link;
use davidhirtz\yii2\skeleton\modules\admin\controllers\DashboardController;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Nav;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;
use yii\helpers\Url;

class NavBar extends Widget
{
    public array $attributes = ['class' => 'navbar'];
    /**
     * @var array|null containing the route of the language dropdown. If not set, the current URL will be used.
     */
    public ?array $languageRoute = null;

    public function render(): string
    {
        $id = 'navbar';

        $container = Container::make()
            ->html(
                Nav::widget([
                    'id' => $id,
                    'items' => $this->getItems(),
                    'options' => ['class' => 'navbar-nav'],
                    'hideOneItem' => false,
                ]),
                Button::make()
                    ->class('navbar-toggler')
                    ->attribute('data-collapse', "#$id")
                    ->attribute('aria-label', Yii::t('skeleton', 'Toggle navigation'))
                    ->html('<span class="navbar-toggler-icon"></span>')
            );

        if ($items = $this->getAccountItems()) {
            $container->addHtml(Nav::widget([
                'items' => $items,
                'options' => ['class' => 'navbar-nav navbar-account-nav'],
                'hideOneItem' => false,
            ]));
        }

        return '<div class="navbar">' . $container->render() . '</div>';
    }

    public function getItems(): array
    {
        return [...$this->getHomeItems(), ...$this->getModuleItems()];
    }

    protected function getModuleItems(): array
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $items = $module->getNavBarItems();

        $this->sortItemsByOrder($items);

        return $items;
    }

    /**
     * @see AccountController::actionLogin()
     * @see AccountController::actionCreate()
     * @see AccountController::actionUpdate()
     * @see AccountController::actionLogout()
     */
    protected function getAccountItems(): array
    {
        $user = Yii::$app->getUser();

        if ($user->getIsGuest()) {
            return [
                [
                    'label' => Yii::t('skeleton', 'Login'),
                    'icon' => 'sign-in-alt',
                    'url' => $user->loginUrl,
                ],
                [
                    'label' => Yii::t('skeleton', 'Sign up'),
                    'icon' => 'plus-circle',
                    'url' => ['/admin/account/create'],
                    'visible' => Yii::$app->getUser()->isSignupEnabled(),
                ],
            ];
        }

        $i18n = Yii::$app->getI18n();

        $dropdown = Dropdown::make()
            ->dropend()
            ->button(Button::make()
                ->class('nav-link')
                ->html(Icon::tag(Yii::$app->language)->collection('flag')));

        foreach ($i18n->getLanguages() as $language) {
            $label = $i18n->getLabel($language);

            $link = Link::make()
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

            $dropdown->items($link);
        }

        return [
            $dropdown->render(),
            [
                'label' => $user->getIdentity()->getUsername(),
                'icon' => 'user',
                'url' => ['/admin/account/update'],
            ],
            [
                'label' => Yii::t('skeleton', 'Logout'),
                'icon' => 'sign-out-alt',
                'url' => '',
                'linkOptions' => [
                    'class' => 'navbar-logout',
                    'hx-post' => Url::toRoute(['/admin/account/logout']),
                    'hx-push-url' => 'true',
                    'hx-target' => 'body',
                ],
            ],
        ];
    }

    /**
     * @see DashboardController::actionIndex()
     */
    protected function getHomeItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Home'),
                'icon' => 'home',
                'url' => ['/admin/dashboard/index'],
            ],
        ];
    }

    /**
     * Sorts items by array key `order` and if not set by array key.
     */
    protected function sortItemsByOrder(array &$items): void
    {
        $keys = array_keys($items);
        sort($keys);

        $orderByKeys = array_flip($keys);

        uksort($items, static function ($a, $b) use ($items, $orderByKeys): int {
            $a = $items[$a]['order'] ?? $orderByKeys[$a];
            $b = $items[$b]['order'] ?? $orderByKeys[$b];
            return $a <=> $b;
        });
    }
}
