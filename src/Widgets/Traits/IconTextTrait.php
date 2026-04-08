<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Stringable;

trait IconTextTrait
{
    use TagContentTrait;
    use IconTrait;

    protected function renderContent(): string|Stringable
    {
        return $this->getIconText();
    }

    protected function getIconText(): string|Stringable
    {
        if ($this->icon && $this->content) {
            return Div::make()
                ->class('icon-text')
                ->content($this->getIcon(), Div::make()->content(...$this->content));
        }

        return $this->getIcon() ?? implode('', $this->content);
    }
}
