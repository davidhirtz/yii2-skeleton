<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagAjaxAttributeTrait;
use Hirtz\Skeleton\Html\Traits\TagIconTextTrait;
use Hirtz\Skeleton\Html\Traits\TagLinkTrait;
use Hirtz\Skeleton\Html\Traits\TagTooltipAttributeTrait;
use Override;

class A extends Tag
{
    use TagAjaxAttributeTrait;
    use TagIconTextTrait;
    use TagLinkTrait;
    use TagTooltipAttributeTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'a';
    }
}
