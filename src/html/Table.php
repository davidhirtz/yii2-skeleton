<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

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

    protected function renderContent(): string
    {
        return $this->header . $this->body;
    }

    protected function getTagName(): string
    {
        return 'table';
    }
}
