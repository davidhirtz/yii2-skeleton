<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\toolbars;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\Button;
use Yii;

class CreateButton extends Tag
{
    protected array|null $href = null;
    protected ?string $label = null;
    protected ?string $icon = null;

    public function href(array|string|null $href): static
    {
        $this->href = $href;
        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function icon(string|null $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function renderContent(): string
    {
        return Button::make()
            ->primary()
            ->text($this->label ?? Yii::t('skeleton', 'Create'))
            ->icon($this->icon ?? 'plus')
            ->href($this->href ?? ['create'])
            ->render();
    }
}
