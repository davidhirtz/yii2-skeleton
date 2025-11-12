<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagTableRowsTrait;
use Override;

class Tbody extends base\Tag
{
    use TagTableRowsTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'tbody';
    }
}
