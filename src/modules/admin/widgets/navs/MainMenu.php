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
            ...$this->getModuleItems(),
        ];
    }

    protected function getModuleItems(): array
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $items = [];

        foreach ($module->getMainMenuItems() as $item) {
            $items[] = NavItem::make()
                ->label($item->label)
                ->url($item->url)
                ->icon($item->icon)
                ->routes($item->routes)
                ->order($item->order);
        }

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
}
