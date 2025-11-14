<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\TableCell;
use davidhirtz\yii2\skeleton\html\base\Tag;

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

    #[\Override]
    protected function renderContent(): string
    {
        return $this->cells ? implode('', $this->cells) : '';
    }

    protected function getTagName(): string
    {
        return 'tr';
    }
}
