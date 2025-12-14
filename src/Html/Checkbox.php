<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Traits\TagInputTrait;
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
        $this->addClass('input')
            ->attributes['type'] ??= 'checkbox';

        return parent::before();
    }
}
