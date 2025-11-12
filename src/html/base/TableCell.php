<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\base;

use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

abstract class TableCell extends Tag
{
    use TagContentTrait;

    final public function colSpan(?int $span): static
    {
        return $this->attribute('colspan', $span);
    }

    final public function rowSpan(?int $span): static
    {
        return $this->attribute('rowspan', $span);
    }
}
