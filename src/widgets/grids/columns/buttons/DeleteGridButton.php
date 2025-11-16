<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\widgets\buttons\traits\DeleteButtonTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\helpers\Url;

class DeleteGridButton extends Widget
{
    use DeleteButtonTrait;

    public function init(): void
    {
        $this->setButtonDefault();
        parent::init();
    }

    public function renderContent(): Stringable
    {
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
