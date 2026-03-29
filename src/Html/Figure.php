<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;

class Figure extends Base\Tag
{
    use TagContentTrait;

    protected function getTagName(): string
    {
        return 'figure';
    }
}
