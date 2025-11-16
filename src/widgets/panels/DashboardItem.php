<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

final class DashboardItem
{
    public function __construct(
        public ?string $label = null,
        public array|string|null $url = null,
        public ?string $icon = null,
        public array $roles = [],
        public array $attributes = [],
    )
    {
    }

    public function merge(self $item): self
    {
        if ($item->label) {
            $this->label = $item->label;
        }

        if ($item->url) {
            $this->url = $item->url;
        }

        if ($item->icon) {
            $this->icon = $item->icon;
        }

        $this->roles = array_unique([...$this->roles, ...$item->roles]);
        $this->attributes = [...$this->attributes, ...$item->attributes];

        return $this;
    }
}