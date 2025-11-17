<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\html\Div;
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
