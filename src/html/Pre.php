<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Override;

class Pre extends base\Tag
{
    use TagContentTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'pre';
    }
}
