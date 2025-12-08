<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Override;

class Table extends base\Tag
{
    private ?Tbody $body = null;
    private ?Thead $header = null;

    public function header(?Thead $header): self
    {
        $this->header = $header;
        return $this;
    }

    public function body(Tbody $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function rows(array $rows): self
    {
        $this->body ??= Tbody::make();

        foreach ($rows as $row) {
            $this->body->addRows(Tr::make()
                ->cells(...array_map(fn (mixed $cell) => Td::make()->content((string)$cell), $row)));
        }

        return $this;
    }

    #[Override]
    protected function renderContent(): string
    {
        return $this->header . $this->body;
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'table';
    }
}
