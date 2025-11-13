<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use Stringable;
use Yii;

class CreateButton implements Stringable
{
    public function __construct(
        private ?string $label = null,
        private readonly array|null $url = ['create'],
        private readonly ?string $icon = 'plus',
    ) {
        $this->label ??= Yii::t('skeleton', 'Create');
    }

    public function __toString(): string
    {
        return Button::make()
            ->primary()
            ->text($this->label)
            ->icon($this->icon)
            ->href($this->url)
            ->render();
    }
}
