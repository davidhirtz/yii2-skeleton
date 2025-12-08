<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\ListTag;
use Override;

class Ol extends ListTag
{
    #[Override]
    protected function getTagName(): string
    {
        return 'ol';
    }
}
