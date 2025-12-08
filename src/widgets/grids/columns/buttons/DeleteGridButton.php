<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids\columns\buttons;

use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\widgets\buttons\traits\DeleteButtonTrait;
use Hirtz\Skeleton\widgets\Modal;
use Hirtz\Skeleton\widgets\Widget;
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
