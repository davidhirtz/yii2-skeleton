<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class Th extends base\TableCell
{
    protected function getTagName(): string
    {
        return 'th';
    }
}
