<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagAjaxAttributeTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLinkTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;
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
