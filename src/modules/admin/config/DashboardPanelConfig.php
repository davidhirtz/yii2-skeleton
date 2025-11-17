<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\config;

/**
 * @implements ConfigInterface<DashboardPanelConfig>
 */
final class DashboardPanelConfig implements ConfigInterface
{
    public function __construct(
        public ?string $name = null,
        public array $items = [],
        public array $roles = [],
        public array $attributes = [],
    ) {
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
