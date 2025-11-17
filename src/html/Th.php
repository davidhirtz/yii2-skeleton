<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use Override;

class Th extends base\TableCell
{
    #[Override]
    protected function getTagName(): string
    {
        return 'th';
    }
}
