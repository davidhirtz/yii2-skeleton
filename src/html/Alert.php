<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class Alert extends Tag
{
    use TagContentTrait;

    private ?Tag $actions = null;

    protected array $attributes = [
        'class' => 'alert',
    ];

    public function actions(Tag|null $html): static
    {
        $this->actions = $html;
        return $this;
    }

    public function status(string $status): static
    {
        return $this->addClass("alert-$status");
    }

    protected function prepareAttributes(): void
    {
        if ($this->actions) {
            Html::addCssClass($this->attributes, 'alert-interactive');
        }

        parent::prepareAttributes();
    }

    protected function renderContent(): string
    {
        $content = implode('', $this->content);

        if ($this->actions) {
            $content = Div::make()->html($content)->render() . $this->actions->render();
        }

        return $content;
    }
}
