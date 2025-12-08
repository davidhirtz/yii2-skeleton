<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\base\Tag;
use Hirtz\Skeleton\html\traits\TagAjaxAttributeTrait;
use Hirtz\Skeleton\html\traits\TagIconTextTrait;
use Hirtz\Skeleton\html\traits\TagLinkTrait;
use Hirtz\Skeleton\html\traits\TagTooltipAttributeTrait;
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
