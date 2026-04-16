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
        $this->content = [];
        return $this->addText(...$content);
    }

    final public function addText(string|Stringable|null ...$content): static
    {
        foreach ($content as $text) {
            $this->content[] = $text instanceof Stringable ? $text : Html::encode($text);
        }

        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        return implode('', $this->content);
    }
}
