<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use Override;

class Div extends Tag
{
    use TagIconTextTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'div';
    }
}
