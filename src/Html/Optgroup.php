<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\ListTag;
use Override;

class Optgroup extends ListTag
{
    /**
     * @var Option[]
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

    #[\Override]
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
