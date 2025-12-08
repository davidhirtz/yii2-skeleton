<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\traits\TagInputTrait;
use Hirtz\Skeleton\html\traits\TagPlaceholderTrait;

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
