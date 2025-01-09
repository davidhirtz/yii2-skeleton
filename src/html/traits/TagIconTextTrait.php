<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\html\Icon;

trait TagIconTextTrait
{
    use TagContentTrait;

    private ?Icon $icon = null;

    public function icon(string $icon): static
    {
        $new = clone $this;
        $new->icon = Icon::tag($icon);
        return $new;
    }

    protected function renderContent(): string
    {
        $html = implode('', $this->content);

        if ($this->icon && $html) {
            return '<div class="icon-text">' . $this->icon->render() . "<div>$html</div></div>";
        }

        return $this->icon?->render() ?? $html;
    }
}
