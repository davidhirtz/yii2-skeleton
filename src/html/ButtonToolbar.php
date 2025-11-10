<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class ButtonToolbar extends Tag
{
    use TagContentTrait;

    protected array $attributes = [
        'class' => 'btn-toolbar',
    ];
}
