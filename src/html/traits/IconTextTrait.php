<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Base\Tag;
use Yiisoft\Html\Tag\Div;

trait IconTextTrait
{
    private string $text = '';
    private ?Icon $icon = null;

    public function icon(string $icon): self
    {
        $new = clone $this;
        $new->icon = Icon::tag($icon);
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

    protected function generateIconTextContent(): string|Tag
    {
        if ($this->icon && $this->text) {
            return Div::tag()
                ->addContent($this->icon)
                ->addContent(Div::tag()->content($this->text))
                ->addClass('icon-text');
        }

        return $this->icon ?? $this->text;
    }
}
