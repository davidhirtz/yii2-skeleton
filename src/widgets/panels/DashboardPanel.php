<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

final class DashboardPanel
{
    /**
     * @param array<string, DashboardItem> $items
     */
    public function __construct(
        public ?string $name = null,
        public array $items = [],
        public array $roles = [],
        public array $attributes = [],
    )
    {
    }

    public function merge(self $panel): self
    {
        if ($panel->name) {
            $this->name = $panel->name;
        }

        foreach ($panel->items as $key => $item) {
            $this->items[$key] = array_key_exists($key, $this->items)
                ? $this->items[$key]->merge($item)
                : $item;
        }

        $this->roles = array_unique([...$this->roles, ...$panel->roles]);
        $this->attributes = [...$this->attributes, ...$panel->attributes];

        return $this;
    }
}