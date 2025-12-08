<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\base\Tag;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Override;

class Noscript extends Tag
{
    use TagContentTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'noscript';
    }
}
