<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Hirtz\Skeleton\Html\Optgroup;
use Hirtz\Skeleton\Html\Option;
use Override;

/**
 * @template T of Option|Optgroup
 */
trait TagSelectTrait
{
    /**
     * @var T[]
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

    #[Override]
    protected function renderContent(): string
    {
        return implode('', $this->options);
    }
}
