<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\base\Tag;
use Hirtz\Skeleton\html\traits\TagIconTextTrait;

class Div extends Tag
{
    use TagIconTextTrait;

    protected function getTagName(): string
    {
        return 'div';
    }
}
