<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\config;

/**
 * @implements ConfigInterface<MainMenuItemConfig>
 */
final class MainMenuItemConfig implements ConfigInterface
{
    public function __construct(
        public ?string $label = null,
        public array|string|null $url = null,
        public ?string $icon = null,
        public array $roles = [],
        public array $routes = [],
        public array $items = [],
        public ?int $order = null,
        public array $attributes = [],
    ) {
    }

    public function merge(ConfigInterface $config): self
    {
        if ($config->label) {
            $this->label = $config->label;
        }

        if ($config->url) {
            $this->url = $config->url;
        }

        if ($config->icon) {
            $this->icon = $config->icon;
        }

        if ($config->order) {
            $this->order = $config->order;
        }

        $this->roles = array_unique([...$this->roles, ...$config->roles]);
        $this->routes = array_unique([...$this->routes, ...$config->routes]);
        $this->attributes = [...$this->attributes, ...$config->attributes];

        $items = [];

        foreach ($config->items as $key => $item) {
            $items = Config::merge($config->items, $key, $item);
        }

        $this->items = $items;

        return $this;
    }
}
