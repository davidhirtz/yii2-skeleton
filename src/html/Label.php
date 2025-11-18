<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\base\Tag;
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

    protected function renderContent(): string|Stringable
    {
        return $this->text;
    }

    protected function getTagName(): string
    {
        return 'label';
    }
}
