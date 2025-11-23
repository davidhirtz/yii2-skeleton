<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;

class Textarea extends base\Tag
{
    use TagInputTrait;

    protected ?string $value = null;

    public function rows(int $rows): static
    {
        return $this->attribute('rows', $rows);
    }

    public function cols(int $cols): static
    {
        return $this->attribute('cols', $cols);
    }

    public function value(?string $value): static
    {
        $this->value = Html::encode($value);
        return $this;
    }

    protected function getTagName(): string
    {
        return 'textarea';
    }
}
