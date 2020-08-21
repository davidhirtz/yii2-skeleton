<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base;

use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Nav;
use Yii;
use yii\helpers\Url;

/**
 * Class NavBar
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base
 */
class NavBar extends \yii\bootstrap4\NavBar
{
    /**
     * @var array
     */
    public $options = [
        'class' => 'navbar navbar-expand-md fixed-top',
    ];

    /**
     * @inheritdoc
     */
    public function run()
    {
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav'],
            'items' => $this->getItems(),
        ]);

        if ($items = $this->getAccountItems()) {
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav'],
                'items' => $items,
            ]);
        }

        parent::run();
    }

    /**
     * Sorts {@link Module::$navbarItems} by key and adds home button.
     * @return array
     */
    public function getItems()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $items = $module->navbarItems;
        ksort($items);

        return array_merge($this->getHomeItems(), $items);
    }

    /**
     * @return array
     */
    protected function getAccountItems()
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
            $items[] = [
                'label' => '<i class="i18n-icon ' . $language . '"></i><span class="i18n-label">' . $i18n->getLabel($language) . '</span>',
                'url' => Url::current(['language' => $language]),
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
     * @return array
     */
    protected function getHomeItems()
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Home'),
                'icon' => 'home',
                'url' => ['/admin/dashboard/index'],
            ],
        ];
    }
}