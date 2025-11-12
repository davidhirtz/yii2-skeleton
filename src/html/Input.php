<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\VoidTag;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use Override;

class Input extends VoidTag
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
    protected function getTagName(): string
    {
        return 'input';
    }
}
