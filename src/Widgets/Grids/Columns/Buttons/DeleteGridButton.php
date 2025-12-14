<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns\Buttons;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Widgets\Buttons\Traits\DeleteButtonTrait;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class DeleteGridButton extends Widget
{
    use DeleteButtonTrait;

    public function renderContent(): Stringable
    {
        $this->setButtonDefault();

        $modal = Modal::make()
            ->title($this->title)
            ->content(...$this->content)
            ->footer(Button::make()
                ->danger()
                ->post($this->url)
                ->text($this->label));

        return Button::make()
            ->danger()
            ->ariaLabel($this->label)
            ->icon($this->icon)
            ->modal($modal);
    }
}
