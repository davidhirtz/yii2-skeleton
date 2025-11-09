<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use Override;

class ButtonToolbar extends Tag
{
    protected array $attributes = [
        'class' => 'btn-toolbar',
    ];

    private array $buttons = [];

    public function button(Button|string $btn): static
    {
        $this->buttons[] = $btn;
        return $this;
    }

    public function buttons(Button|string ...$buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }

    #[Override]
    protected function renderContent(): string
    {
        return implode('', $this->buttons);
    }
}
