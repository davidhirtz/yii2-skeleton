<?php

namespace davidhirtz\yii2\skeleton\helpers\html;

use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Base\NormalTag;
use Yiisoft\Html\Tag\Span;

final class Btn extends NormalTag
{
    private string $text = '';
    private ?Icon $icon = null;

    public function icon(string $icon): self
    {
        $this->icon = Icon::tag($icon);
        return $this->addClass('icon-text');
    }

    public function text(string $text): self
    {
        $this->text = Html::encode($text);
        return $this;
    }

    protected function generateContent(): string
    {
        return $this->icon ? Span::tag()->content($this->text)->render() : $this->text;
    }

    protected function prepend(): string
    {
        return $this->icon?->__toString() ?? '';
    }

    protected function getName(): string
    {
        return 'button';
    }
}
