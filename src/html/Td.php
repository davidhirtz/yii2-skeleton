<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class Td extends base\TableCell
{
    protected function getTagName(): string
    {
        return 'td';
    }
}
