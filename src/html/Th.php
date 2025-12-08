<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Override;

class Th extends base\TableCell
{
    #[Override]
    protected function getTagName(): string
    {
        return 'th';
    }
}
