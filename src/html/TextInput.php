<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\html\traits\TagPlaceholderTrait;

class TextInput extends Input
{
    use TagInputTrait;
    use TagPlaceholderTrait;

    #[\Override]
    protected function before(): string
    {
        $this->addClass('input');
        $this->attributes['type'] ??= 'text';

        return parent::before();
    }
}
