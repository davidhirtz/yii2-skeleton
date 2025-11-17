<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use Override;

class Td extends base\TableCell
{
    #[Override]
    protected function getTagName(): string
    {
        return 'td';
    }
}
