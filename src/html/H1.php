<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class H1 extends base\Tag
{
    use TagContentTrait;

    protected function getTagName(): string
    {
        return 'h1';
    }
}
