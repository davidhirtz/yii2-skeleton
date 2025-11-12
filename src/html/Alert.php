<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use Override;

class Alert extends Tag
{
    use TagContentTrait;

    protected array $attributes = [
        'class' => 'alert',
    ];

    private array $buttons = [];
    private ?Icon $icon = null;
    private ?string $status = null;

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

    public function icon(?string $icon): static
    {
        $this->icon = $icon ? Icon::tag($icon) : null;
        return $this;
    }

    public function status(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    protected function prepareAttributes(): void
    {
        if ($this->status) {
            Html::addCssClass($this->attributes, "alert-$this->status");
        }

        parent::prepareAttributes();
    }

    #[Override]
    protected function renderContent(): string
    {
        $content = Div::make()
            ->class('grow')
            ->html(...$this->content)
            ->render();

        if ($this->icon) {
            $content = Div::make()->class('icon')->html($this->icon) . $content;
        }

        if ($this->buttons) {
            $content .= Div::make()
                ->html(...$this->buttons)
                ->addClass('alert-buttons');
        }

        return $content;
    }

    protected function getTagName(): string
    {
        return 'div';
    }
}
