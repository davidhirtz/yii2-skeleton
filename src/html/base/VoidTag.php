<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\base;

use Override;

abstract class VoidTag extends Tag
{
    #[Override]
    protected function getTag(): string
    {
        return '<' . $this->getTagName() . $this->getAttributes() . '>';
    }
}
