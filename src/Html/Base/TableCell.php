<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Base;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;

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
