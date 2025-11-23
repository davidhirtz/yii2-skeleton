<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\ListTag;
use Override;

class Ol extends ListTag
{
    #[Override]
    protected function getTagName(): string
    {
        return 'ol';
    }
}
