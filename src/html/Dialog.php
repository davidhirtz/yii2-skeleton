<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class Dialog extends Tag
{
    use TagContentTrait;

    protected function getTagName(): string
    {
        return 'dialog';
    }
}
