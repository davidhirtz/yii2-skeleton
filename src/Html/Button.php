<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;

class Button extends Base\Tag
{
    use TagContentTrait;

    protected function getAttributes(): string
    {
        $this->attributes['type'] ??= 'button';

        return parent::getAttributes();
    }

    protected function getTagName(): string
    {
        return 'button';
    }
}
