<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Closure;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * @property Module $module
 */
class Dashboard extends Widget
{
    use ContainerTrait;

    /**
     * @var DashboardItem[]
     */
    protected array $items = [];
    protected Module $module;

    public function __construct(array $config = [])
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $this->module = $module;

        $this->module->dashboard($this);

        parent::__construct($config);
    }

    /**
     * @param DashboardItem[]|Closure(DashboardItem[]):DashboardItem[] $items
     * @return $this
     */
    public function items(array|Closure $items): static
    {
        $this->items = $items instanceof Closure ? $items($this->items) : array_filter($items);
        return $this;
    }

    public function addItem(DashboardItem $item): static
    {
        $this->items[] = $item;
        return $this;
    }

    public function addItems(DashboardItem ...$items): static
    {
        $this->items = [...$this->items, ...$items];
        return $this;
    }

    #[Override]
    protected function renderContent(): Stringable
    {
        return Ul::make()
            ->class('dashboard')
            ->content(...$this->items);
    }
}
