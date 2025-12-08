<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\ListTag;
use Override;

class Ul extends ListTag
{
    #[Override]
    protected function getTagName(): string
    {
        return 'ul';
    }
}
