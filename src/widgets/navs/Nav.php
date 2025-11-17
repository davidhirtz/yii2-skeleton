<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\widgets\navs\traits\NavItemTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Nav extends Widget
{
    use NavItemTrait;
    use TagAttributesTrait;

    protected bool $hideSingleItem = true;

    public function showSingleItem(): static
    {
        $this->hideSingleItem = false;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $items = array_filter($this->items, fn (NavItem $item) => $item->isVisible());

        if (!$items || (1 === count($items) && $this->hideSingleItem)) {
            return '';
        }

        return Ul::make()
            ->attributes($this->attributes)
            ->addClass('nav')
            ->content(...$items);
    }
}
