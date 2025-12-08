<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids;

use Hirtz\Skeleton\html\traits\TagCardTrait;
use Hirtz\Skeleton\widgets\grids\traits\GridTrait;
use Hirtz\Skeleton\widgets\panels\Card;
use Hirtz\Skeleton\widgets\traits\ContainerWidgetTrait;
use Hirtz\Skeleton\widgets\Widget;
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
