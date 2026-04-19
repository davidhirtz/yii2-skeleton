<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class ButtonGroup extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;

    protected function renderContent(): string|Stringable
    {
        return Div::make()
            ->attributes($this->attributes)
            ->addClass('btn-group')
            ->content(...$this->content);
    }
}
