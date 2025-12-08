<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\html\base\Tag;
use Stringable;

class Label extends Tag
{
    protected string $text;

    public function for(string $id): static
    {
        $this->attributes['for'] = $id;
        return $this;
    }

    final public function text(string|Stringable $text): static
    {
        $this->text = Html::encode($text);
        return $this;
    }

    #[\Override]
    protected function renderContent(): string|Stringable
    {
        return $this->text;
    }

    protected function getTagName(): string
    {
        return 'label';
    }
}
