<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Hirtz\Skeleton\Helpers\Html;
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
            ...array_map(Html::encode(...), array_values(array_filter($content))),
        ];

        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        return implode('', $this->content);
    }
}
