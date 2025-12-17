<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Override;

class H1 extends Base\Tag
{
    use TagContentTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'h1';
    }
}
