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

    public function addCells(TableCell ...$cells): self
    {
        $this->cells = array_merge($this->cells, $cells);
        return $this;
    }

    protected function renderContent(): string
    {
        return $this->cells ? implode('', $this->cells) : '';
    }

    protected function getTagName(): string
    {
        return 'tr';
    }
}
