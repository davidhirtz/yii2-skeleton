<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;

class Checkbox extends Input
{
    use TagInputTrait;

    public array $attributes = [
        'class' => 'form-check-input',
        'type' => 'checkbox',
    ];

    public function checked(bool $checked = true): static
    {
        return $this->attribute('checked', $checked);
    }
}
