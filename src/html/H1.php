<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\traits\TagContentTrait;
use Override;

class H1 extends base\Tag
{
    use TagContentTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'h1';
    }
}
