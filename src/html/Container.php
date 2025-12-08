<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\base\Tag;
use Hirtz\Skeleton\html\traits\TagContentTrait;

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
