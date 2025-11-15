<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Icon;
use Stringable;

trait TagIconTextTrait
{
    use TagContentTrait;

    private ?Icon $icon = null;

    public function icon(string|Icon|null $icon, array $attributes = []): static
    {
        $this->icon = $icon
            ? ($icon instanceof Icon ? $icon : Icon::make()->name($icon))
                ->addAttributes($attributes)
            : null;

        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        if ($this->icon && $this->content) {
            return Div::make()
                ->class('icon-text')
                ->addContent($this->icon)
                ->addContent(
                    Div::make()
                        ->content(...$this->content)
                );
        }

        return $this->icon ?? implode('', $this->content);
    }
}
