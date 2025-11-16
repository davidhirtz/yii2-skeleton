<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use Closure;

final class NavItem
{
    public function __construct(
        public ?string $label = null,
        public array|string|null $url = null,
        public array|string|Closure|null $active = null,
        public bool|Closure|null $visible = null,
        public array $items = [],
        public array $roles = [],
        public string|int|null $badge = null,
        public ?string $icon = null,
        public array $attributes = [],
        public array $linkAttributes = [],
        public array $badgeAttributes = [],
        public array $iconAttributes = [],
        public string $template = '{icon}{label}{badge}',
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

        if ($item->active) {
            $this->active = is_array($item->active) && is_array($this->active)
                ? [...$this->active, ...$item->active]
                : $item->active;
        }

        if ($item->icon) {
            $this->icon = $item->icon;
        }

        $this->roles = array_unique([...$this->roles, ...$item->roles]);

        $this->attributes = [...$this->attributes, ...$item->attributes];
        $this->linkAttributes = [...$this->linkAttributes, ...$item->linkAttributes];
        $this->badgeAttributes = [...$this->badgeAttributes, ...$item->badgeAttributes];
        $this->iconAttributes = [...$this->iconAttributes, ...$item->iconAttributes];

        return $this;
    }
}