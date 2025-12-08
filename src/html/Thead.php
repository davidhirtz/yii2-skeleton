<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\traits\TagTableRowsTrait;
use Override;

class Thead extends base\Tag
{
    use TagTableRowsTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'thead';
    }
}
