<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\widgets\navs\Nav;
use davidhirtz\yii2\skeleton\widgets\navs\NavItem;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;

class MainMenu extends Widget
{
    public array $attributes = [
        'id' => 'menu',
        'class' => 'navbar-nav navbar-left nav',
    ];

    protected function renderContent(): Stringable
    {
        return Nav::make()
            ->attributes($this->attributes)
            ->items(...$this->getItems())
            ->showSingleItem();
    }

    protected function getItems(): array
    {
        return [
            $this->getHomeItem(),
//            ...$this->getModuleItems(),
        ];
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
    protected function getHomeItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'Home'))
            ->url(['/admin/dashboard/index'])
            ->icon('home');
    }

    /**
     * Sorts items by array key `order` and if not set by array key.
     */
    protected function sortItemsByOrder(array &$items): void
    {

    }
}
