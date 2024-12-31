<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\html\Icon;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Span;

trait IconTextTrait
{
    private string $text = '';
    private ?Icon $icon = null;

    public function icon(string $icon, string $collection = 'fa'): self
    {
        $new = clone $this;
        $new->icon = Icon::tag()->icon($icon)->collection($collection);
        return $new;
    }

    public function text(string|null $text): self
    {
        if ($text !== null) {
            $new = clone $this;
            $new->text = Html::encode($text);
            return $new;
        }

        return $this;
    }

    protected function generateIconTextContent(): string
    {
        if ($this->icon && $this->text) {
            return Div::tag()
                ->addContent($this->icon->render())
                ->addContent(Span::tag()->content($this->text)->render())
                ->addClass('icon-text')
                ->encode(false)
                ->render();
        }

        return $this->icon?->render() ?? $this->text;
    }
}
