<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Hirtz\Skeleton\Html\Traits\TagCardTrait;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Traits\ContainerWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class GridContainer extends Widget
{
    use ContainerWidgetTrait;
    use GridTrait;
    use TagCardTrait;

    protected function renderContent(): string|Stringable
    {
        $content = $this->grid->render();

        return $content
            ? Card::make()
                ->title($this->title)
                ->collapsed($this->collapsed)
                ->content($content)
            : '';
    }
}
