<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use Override;

class Select extends Tag
{
    use TagInputTrait;

    /**
     * @var Option[]|Optgroup[]
     */
    protected array $options = [];

    public function options(Option|Optgroup ...$option): static
    {
        $this->options = $option;
        return $this;
    }

    public function addOption(Option|Optgroup ...$option): static
    {
        $this->options = [...$this->options, ...$option];
        return $this;
    }

    public function size(int $size): static
    {
        $this->attributes['size'] = $size;
        return $this;
    }

    public function multiple(): static
    {
        $this->attributes['multiple'] = true;
        return $this;
    }

    #[\Override]
    protected function renderContent(): string
    {
        return implode('', $this->options);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'select';
    }
}
