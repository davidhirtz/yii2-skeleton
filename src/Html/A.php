<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagLinkTrait;
use Override;

class A extends Tag
{
    use TagContentTrait;
    use TagLinkTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'a';
    }
}
