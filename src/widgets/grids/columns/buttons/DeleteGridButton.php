<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\widgets\buttons\traits\DeleteButtonTrait;
use Stringable;

class DeleteGridButton extends GridButton
{
    use DeleteButtonTrait;
    use TagTitleTrait;

    public function init(): void
    {
        $this->setButtonDefault();
        parent::init();
    }

    public function renderContent(): Stringable
    {
        $modal = Modal::make()
            ->title($this->title)
            ->footer(Button::make()
                ->danger()
                ->post($this->href)
                ->text($this->label));

        return Button::make()
            ->danger()
            ->icon($this->icon)
            ->modal($modal);
    }
}
