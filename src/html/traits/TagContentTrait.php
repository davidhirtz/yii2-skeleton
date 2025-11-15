<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use Stringable;

trait TagContentTrait
{
    protected array $content = [];

    final public function content(string|Stringable|null ...$content): static
    {
        $this->content = array_values(array_filter($content));
        return $this;
    }

    final public function addContent(string|Stringable|null ...$content): static
    {
        $this->content = [...$this->content, ...array_values(array_filter($content))];
        return $this;
    }

    final public function text(string|Stringable|null ...$content): static
    {
        $this->content = array_values(array_filter(array_map(Html::encode(...), $content)));
        return $this;
    }

    final public function addText(string|Stringable|null ...$content): static
    {
        $this->content = [
            ...$this->content,
            array_map(Html::encode(...), array_values(array_filter($content))),
        ];

        return $this;
    }

    final public function hasContent(): bool
    {
        return [] !== array_filter($this->content);
    }

    protected function renderContent(): string|Stringable
    {
        return implode('', $this->content);
    }
}
