<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class BtnToolbar extends Tag
{
    protected array $attributes = [
        'class' => 'btn-toolbar',
    ];

    private array $buttons = [];

    public function button(Button $btn): static
    {
        $this->buttons[] = $btn;
        return $this;
    }

    public function buttons(Button ...$buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }

    protected function renderContent(): string
    {
        return implode('', $this->buttons);
    }
}
