<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Traits\TagTableRowsTrait;
use Override;

class Thead extends Base\Tag
{
    use TagTableRowsTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'thead';
    }
}
