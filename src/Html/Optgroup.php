<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagSelectTrait;
use Override;

class Optgroup extends Tag
{
    /** @use TagSelectTrait<Option> */
    use TagSelectTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'optgroup';
    }
}
