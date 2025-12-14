<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagIconTextTrait;

class Div extends Tag
{
    use TagIconTextTrait;

    protected function getTagName(): string
    {
        return 'div';
    }
}
