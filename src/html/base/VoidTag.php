<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\base;

use Override;

abstract class VoidTag extends Tag
{
    #[Override]
    protected function renderTag(): string
    {
        return '<' . $this->getTagName() . $this->renderAttributes() . '>';
    }
}
