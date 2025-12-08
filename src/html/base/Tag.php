<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\base;

use Override;
use Stringable;

abstract class Tag extends AbstractTag
{
    #[Override]
    protected function getTag(): string
    {
        return '<' . $this->getTagName() . $this->getAttributes() . '>' . $this->renderContent() . '</' . $this->getTagName() . '>';
    }

    protected function renderContent(): string|Stringable
    {
        return '';
    }
}
