<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use Override;

class Input extends Tag
{
    use TagInputTrait;

    protected function prepareAttributes(): void
    {
        if (!array_key_exists('name', $this->attributes)) {
            $this->getId();
        }

        parent::prepareAttributes();
    }

    #[Override]
    protected function renderTag(): string
    {
        return '<' . $this->getTagName() . $this->renderAttributes() . '>';
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'input';
    }
}
