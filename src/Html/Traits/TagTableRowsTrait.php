<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Hirtz\Skeleton\Html\Tr;

trait TagTableRowsTrait
{
    /**
     * @var Tr[]
     */
    protected array $rows = [];

    final public function rows(Tr ...$content): static
    {
        $this->rows = array_values($content);
        return $this;
    }

    final public function addRows(Tr ...$content): static
    {
        $this->rows = [...$this->rows, ...array_values($content)];
        return $this;
    }

    protected function renderContent(): string
    {
        return implode('', $this->rows);
    }
}
