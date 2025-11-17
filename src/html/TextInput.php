<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\html\traits\TagPlaceholderTrait;

class TextInput extends Input
{
    use TagInputTrait;
    use TagPlaceholderTrait;

    protected function before(): string
    {
        $this->addClass('form-control');
        $this->attributes['type'] ??= 'text';

        return parent::before();
    }
}
