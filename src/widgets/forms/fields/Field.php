<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Field extends Widget
{
    use TagLabelTrait;
    use TagAttributesTrait;
    use TagIdTrait;

    public array $labelAttributes = [];
    public array $contentAttributes = [];

    public array $inputAttributes = [];

    protected mixed $value = null;

    public function value(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
    }

    public function getLabel(): string|Stringable
    {
        return '';
    }

    public function getContent(): string|Stringable
    {
        return '';
    }

    public function getHint(): string|Stringable
    {
        return '';
    }

    public function getError(): string|Stringable
    {
        return '';
    }
}