<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Hirtz\Skeleton\Html\Optgroup;
use Hirtz\Skeleton\Html\Option;
use Override;

trait TagSelectTrait
{
    /**
     * @var list<Option|Optgroup>
     */
    protected array $options = [];

    public function options(Option|Optgroup ...$option): static
    {
        $this->options = array_values($option);
        return $this;
    }

    public function addOption(Option|Optgroup ...$option): static
    {
        $this->options = [...$this->options, ...array_values($option)];
        return $this;
    }

    #[Override]
    protected function renderContent(): string
    {
        return implode('', $this->options);
    }
}
