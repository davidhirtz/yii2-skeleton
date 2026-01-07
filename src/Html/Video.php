<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Override;

class Video extends Tag
{
    #[Override]
    protected function getTagName(): string
    {
        return 'video';
    }
}
