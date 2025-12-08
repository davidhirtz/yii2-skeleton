<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\base;

use Override;

abstract class VoidTag extends AbstractTag
{
    #[Override]
    protected function getTag(): string
    {
        return '<' . $this->getTagName() . $this->getAttributes() . '>';
    }
}
