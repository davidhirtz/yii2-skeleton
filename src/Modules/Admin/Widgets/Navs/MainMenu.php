<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Config\ConfigInterface;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class MainMenu extends Widget
{
    public array $attributes = [
        'class' => 'aside-nav nav',
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
            $items[] = $item instanceof ConfigInterface
                ? NavItem::make()
                    ->label($item->label)
                    ->url($item->url)
                    ->icon($item->icon)
                    ->routes($item->routes)
                    ->order($item->order)
                : $item;
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
