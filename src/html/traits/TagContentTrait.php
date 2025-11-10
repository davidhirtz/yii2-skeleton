<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use Stringable;

trait TagContentTrait
{
    protected array $content = [];

    final public function html(string|Stringable ...$content): static
    {
        $this->content = array_values($content);
        return $this;
    }

    final public function addHtml(string|Stringable ...$content): static
    {
        $this->content = [...$this->content, ...array_values($content)];
        return $this;
    }

    final public function text(string|Stringable ...$content): static
    {
        $this->content = array_values(array_map(Html::encode(...), $content));
        return $this;
    }

    final public function addText(string|Stringable ...$content): static
    {
        $this->content = [
            ...$this->content,
            array_map(Html::encode(...), array_values($content)),
        ];

        return $this;
    }

    protected function renderContent(): string
    {
        return implode('', $this->content);
    }
}
