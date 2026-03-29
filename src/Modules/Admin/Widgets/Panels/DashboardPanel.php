<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Modules\Admin\Config\Config;
use Hirtz\Skeleton\Modules\Admin\Config\ConfigInterface;
use Hirtz\Skeleton\Modules\Admin\Config\DashboardItem;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;
use Hirtz\Skeleton\Widgets\Widget;

/**
 * @implements ConfigInterface<DashboardPanel>
 */
final class DashboardPanel extends Widget implements ConfigInterface
{
    public function __construct(
        public ?string $name = null,
        /** @var DashboardItem[] */
        public array $items = [],
        public array $roles = [],
        public array $attributes = [],
    ) {
        parent::__construct();
    }

    protected function renderContent(): Stack
    {
        $list = Stack::make();

        foreach ($this->items as $item) {
            $list->addItem(StackItem::make()
                ->attributes($item->attributes)
                ->label($item->label)
                ->url($item->url)
                ->roles($item->roles)
                ->icon($item->icon));
        }

        return $list;
    }

    public function merge(ConfigInterface $config): self
    {
        if ($config->name) {
            $this->name = $config->name;
        }

        $items = [];

        foreach ($config->items as $key => $item) {
            $items = Config::merge($config->items, $key, $item);
        }

        $this->items = $items;

        $this->roles = array_unique([...$this->roles, ...$config->roles]);
        $this->attributes = [...$this->attributes, ...$config->attributes];

        return $this;
    }
}
