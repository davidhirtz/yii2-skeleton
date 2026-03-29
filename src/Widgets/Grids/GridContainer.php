<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\CardTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class GridContainer extends Widget
{
    use ContainerTrait;
    use GridTrait;
    use CardTrait;

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
