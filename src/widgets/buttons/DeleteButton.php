<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\buttons;

use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\widgets\buttons\traits\DeleteButtonTrait;
use Hirtz\Skeleton\widgets\Modal;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;

class DeleteButton extends Widget
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
                ->post($this->url, true)
                ->text($this->label));

        return Button::make()
            ->danger()
            ->text($this->label)
            ->icon($this->icon)
            ->modal($modal);
    }
}
