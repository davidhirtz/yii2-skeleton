<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;

class ListGroupItemLink extends Link
{
    use TagVisibilityTrait;

    protected array $attributes = [
        'class' => 'list-group-item list-group-item-action',
    ];
}
