<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\html\Icon;
use Yiisoft\Html\Tag\Base\TagContentTrait;

trait TagIconTextTrait
{
    use TagContentTrait {
        generateContent as generateContentTrait;
    }

    private string $text = '';
    private ?Icon $icon = null;

    public function icon(string $icon): self
    {
        $new = clone $this;
        $new->icon = Icon::tag($icon);
        return $new;
    }

    protected function generateContent(): string
    {
        $text = $this->generateContentTrait();

        if ($this->icon && $text) {
            return '<div class="icon-text">' . $this->icon->render() . "<div>$text</div></div>";
        }

        return $this->icon?->render() ?? $text;
    }
}
