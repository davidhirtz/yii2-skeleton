<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Base;

use Override;
use Stringable;

abstract class Tag extends AbstractTag
{
    protected bool $showEmpty = true;

    #[Override]
    protected function getTag(): string
    {
        $content = (string)$this->renderContent();

        return $content !== '' || $this->showEmpty
            ? ('<' . $this->getTagName() . $this->getAttributes() . '>' . $content . '</' . $this->getTagName() . '>')
            : '';
    }

    protected function renderContent(): string|Stringable
    {
        return '';
    }
}
