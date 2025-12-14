<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Html\Traits\TagPlaceholderTrait;

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
