<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use Override;

class Checkbox extends Input
{
    use TagInputTrait;

    public function checked(bool $checked = true): static
    {
        return $this->attribute('checked', $checked);
    }

    #[Override]
    protected function before(): string
    {
        $this->addClass('form-check-input')
            ->attributes['type'] ??= 'checkbox';

        return parent::before();
    }
}
