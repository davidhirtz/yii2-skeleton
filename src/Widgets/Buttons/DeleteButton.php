<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Widgets\Buttons\Traits\DeleteButtonTrait;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class DeleteButton extends Widget
{
    use DeleteButtonTrait;

    #[Override]
    public function renderContent(): Stringable
    {
        // todo change this to constructor
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
