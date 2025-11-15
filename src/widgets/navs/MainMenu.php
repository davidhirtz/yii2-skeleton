<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\web\User;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Nav;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;

class MainMenu extends Widget
{
    public array $attributes = [
        'id' => 'menu',
        'class' => 'navbar-nav navbar-left nav',
    ];

    public bool $hideSingleItem = true;
    protected User $user;

    public function init(): void
    {
        $this->user ??= Yii::$app->getUser();
        parent::init();
    }

    protected function render(): string
    {
        return Nav::widget([
            'items' => $this->getItems(),
            'options' => $this->attributes,
            'hideOneItem' => $this->hideSingleItem,
        ]);
    }

    protected function getItems(): array
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
