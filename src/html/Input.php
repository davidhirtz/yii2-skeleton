<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\VoidTag;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use Override;

class Input extends VoidTag
{
    use TagInputTrait;

    #[Override]
    protected function before(): string
    {
        if (!array_key_exists('name', $this->attributes)) {
            $this->getId();
        }

        return parent::before();
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'input';
    }
}
