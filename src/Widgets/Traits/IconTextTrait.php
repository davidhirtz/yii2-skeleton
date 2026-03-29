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
        return $this->renderIconText();
    }

    protected function renderIconText(): string|Stringable
    {
        if ($this->icon && $this->content) {
            return Div::make()
                ->class('icon-text')
                ->content($this->icon, Div::make()->content(...$this->content));
        }

        return $this->icon ?? implode('', $this->content);
    }
}
