<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class Alert extends Tag
{
    use TagContentTrait;

    protected array $attributes = [
        'class' => 'alert',
    ];

    public function status(string $status): static
    {
        return $this->addClass("alert-$status");
    }
}
