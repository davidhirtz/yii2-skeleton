<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class Div extends Tag
{
    use TagContentTrait;

    protected function getName(): string
    {
        return 'div';
    }
}
