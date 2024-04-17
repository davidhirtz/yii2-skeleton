<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\DashboardController;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Nav;
use Yii;
use yii\helpers\Url;

class NavBar extends \yii\bootstrap4\NavBar
{
    /**
     * @var array|null containing the route of the language dropdown. If not set, the current URL will be used.
     */
    public ?array $languageRoute = null;

    public $options = [
        'class' => 'navbar navbar-expand-md fixed-top',
    ];

    public function run(): void
    {
        echo Nav::widget([
            'items' => $this->getItems(),
            'options' => ['class' => 'navbar-nav'],
            'hideOneItem' => false,
        ]);

        if ($items = $this->getAccountItems()) {
            echo Nav::widget([
                'items' => $items,
                'options' => ['class' => 'navbar-nav'],
                'hideOneItem' => false,
            ]);
        }

        parent::run();
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
        $items = [];

        foreach ($i18n->getLanguages() as $language) {
            $label = $i18n->getLabel($language);

            $items[] = [
                'label' => "<i class=\"i18n-icon$language\"></i><span class=\"i18n-label\">$label</span>",
                'url' => $this->languageRoute
                    ? Url::toRoute([
                        ...Yii::$app->getRequest()->getQueryParams(),
                        ...$this->languageRoute,
                        'language' => $language,
                    ])
                    : Url::current(['language' => $language]),
                'encode' => false,
            ];
        }

        return [
            [
                'label' => '<i class="i18n-icon ' . Yii::$app->language . '"></i>',
                'icon' => false,
                'url' => '#', // Bootstrap 4.2 fix
                'visible' => count($items) > 1,
                'encode' => false,
                'items' => $items,
                'options' => [
                    'class' => 'i18n-dropdown',
                ],
            ],
            [
                'label' => $user->getIdentity()->getUsername(),
                'icon' => 'user',
                'url' => ['/admin/account/update'],
            ],
            [
                'label' => Yii::t('skeleton', 'Logout'),
                'icon' => 'sign-out-alt',
                'url' => ['/admin/account/logout'],
                'linkOptions' => [
                    'data-method' => 'post',
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

        uksort($items, static function ($a, $b) use ($items, $orderByKeys) {
            $a = $items[$a]['order'] ?? $orderByKeys[$a];
            $b = $items[$b]['order'] ?? $orderByKeys[$b];
            return $a <=> $b;
        });
    }
}
