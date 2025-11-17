<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;

class Div extends Tag
{
    use TagIconTextTrait;

    protected function getTagName(): string
    {
        return 'div';
    }
}
