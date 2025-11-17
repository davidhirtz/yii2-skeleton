<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use Override;

class H1 extends base\Tag
{
    use TagContentTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'h1';
    }
}
