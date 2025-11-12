<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class Li extends Tag
{
    use TagContentTrait;

    #[\Override]
    protected function getTagName(): string
    {
        return 'li';
    }
}
