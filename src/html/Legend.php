<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;

class Legend extends Tag
{
    use TagContentTrait;

    protected function getTagName(): string
    {
        return 'legend';
    }
}
