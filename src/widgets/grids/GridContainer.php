<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids;

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\widgets\grids\traits\GridTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;

class GridContainer extends Widget
{
    use GridTrait;
    use ContainerWidgetTrait;

    protected ?string $title = null;

    public function title(string|null $title): static
    {
        $this->title = $title;
        return $this;
    }

    protected function renderContent(): string
    {
        return Card::make()
            ->title($this->title)
            ->content($this->grid->render())
            ->render();
    }
}
