<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;

class Container extends Tag
{
    use TagContentTrait;

    public function centered(): static
    {
        return $this->addClass('container-centered');
    }

    #[\Override]
    protected function before(): string
    {
        $this->addClass('container');
        return parent::before();
    }

    protected function getTagName(): string
    {
        return 'div';
    }
}
