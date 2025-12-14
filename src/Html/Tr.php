<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\TableCell;
use Hirtz\Skeleton\Html\Base\Tag;
use Override;

class Tr extends Tag
{
    /**
     * @var TableCell[]
     */
    protected array $cells = [];

    public function cells(TableCell ...$cells): self
    {
        $this->cells = $cells;
        return $this;
    }

    /**
     * This currently needs string support for simpler usage without creating TableCell instances for GridView rows.
     */
    public function addCells(TableCell|string ...$cells): self
    {
        $this->cells = [...$this->cells, ...$cells];
        return $this;
    }

    #[Override]
    protected function renderContent(): string
    {
        return $this->cells ? implode('', $this->cells) : '';
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'tr';
    }
}
