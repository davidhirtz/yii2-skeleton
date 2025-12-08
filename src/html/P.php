<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\base\Tag;
use Hirtz\Skeleton\html\traits\TagContentTrait;

class P extends Tag
{
    use TagContentTrait;

    #[\Override]
    protected function getTagName(): string
    {
        return 'p';
    }
}
