<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\html\traits\TagPlaceholderTrait;

class TextInput extends Input
{
    use TagInputTrait;
    use TagPlaceholderTrait;

    public array $attributes = [
        'class' => 'form-control',
        'type' => 'text',
    ];
}
