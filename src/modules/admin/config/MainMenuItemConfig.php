<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\config;

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
        public array $attributes = [],
    )
    {
    }

    public function merge(ConfigInterface $config): self
    {
        foreach ($config->items as $key => $item) {
            Config::merge($config->items, $key, $item);
        }

        if ($config->label) {
            $this->label = $config->label;
        }

        if ($config->url) {
            $this->url = $config->url;
        }

        if ($config->icon) {
            $this->icon = $config->icon;
        }

        $this->roles = array_unique([...$this->roles, ...$config->roles]);
        $this->routes = array_unique([...$this->routes, ...$config->routes]);
        $this->attributes = [...$this->attributes, ...$config->attributes];

        return $this;
    }
}
