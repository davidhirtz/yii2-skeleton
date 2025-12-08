<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\base\VoidTag;
use Hirtz\Skeleton\html\traits\TagInputTrait;
use Override;

class Input extends VoidTag
{
    use TagInputTrait;

    #[Override]
    protected function before(): string
    {
        if (!array_key_exists('name', $this->attributes)) {
            $this->getId();
        }

        $this->attributes['type'] ??= 'text';

        return parent::before();
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'input';
    }
}
