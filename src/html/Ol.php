<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\base\ListTag;
use Override;

class Ol extends ListTag
{
    #[Override]
    protected function getTagName(): string
    {
        return 'ol';
    }
}
