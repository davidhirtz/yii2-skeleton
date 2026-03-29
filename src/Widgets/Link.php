<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagLinkTrait;
use Hirtz\Skeleton\Widgets\Buttons\Traits\AjaxAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTextTrait;
use Hirtz\Skeleton\Widgets\Traits\TooltipAttributeTrait;
use Stringable;

class Link extends Widget
{
    use AjaxAttributesTrait;
    use TagAttributesTrait;
    use TagContentTrait;
    use IconTextTrait;
    use TagLinkTrait;
    use TooltipAttributeTrait;

    public function url(array|string|null $url): static
    {
        return $this->href($url);
    }

    protected function renderContent(): string|Stringable
    {
        return A::make()
            ->attributes($this->attributes)
            ->content($this->renderIconText());
    }
}
