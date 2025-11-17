<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\widgets\buttons\traits\DeleteButtonTrait;
use davidhirtz\yii2\skeleton\widgets\Modal;
use davidhirtz\yii2\skeleton\widgets\Widget;
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
