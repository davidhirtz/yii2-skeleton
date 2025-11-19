<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\ListTag;
use Override;

class Optgroup extends ListTag
{
    /**
     * @var Option[] $options
     */
    protected array $options = [];

    public function options(Option ...$option): static
    {
        $this->options = $option;
        return $this;
    }

    public function addOption(Option ...$option): static
    {
        $this->options = [...$this->options, ...$option];
        return $this;
    }

    protected function renderContent(): string
    {
        return implode('', $this->options);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'optgroup';
    }
}