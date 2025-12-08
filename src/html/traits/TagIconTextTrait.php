<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\traits;

use Hirtz\Skeleton\html\Div;
use Stringable;

trait TagIconTextTrait
{
    use TagContentTrait;
    use TagIconTrait;

    protected function renderContent(): string|Stringable
    {
        if ($this->icon && $this->content) {
            return Div::make()
                ->class('icon-text')
                ->addContent($this->icon)
                ->addContent(
                    Div::make()
                        ->content(...$this->content)
                );
        }

        return $this->icon ?? implode('', $this->content);
    }
}
