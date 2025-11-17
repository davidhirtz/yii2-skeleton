<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\html\Icon;

trait TagIconTrait
{
    public array $iconAttributes = [];
    protected ?Icon $icon = null;

    public function icon(string|Icon|null $icon): static
    {
        $this->icon = is_string($icon) ? Icon::make()->name($icon) : $icon;
        return $this;
    }

    public function iconAttributes(array $attributes): static
    {
        $this->iconAttributes = $attributes;
        return $this;
    }
}
