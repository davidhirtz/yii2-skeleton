<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids;

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\traits\TagCardTrait;
use davidhirtz\yii2\skeleton\widgets\grids\traits\GridTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class GridContainer extends Widget
{
    use ContainerWidgetTrait;
    use GridTrait;
    use TagCardTrait;

    protected function renderContent(): Stringable
    {
        $card = Card::make()
            ->title($this->title)
            ->collapsed($this->collapsed)
            ->content($this->grid);

        return $card;
    }
}
