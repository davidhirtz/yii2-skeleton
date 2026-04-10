<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Closure;
use Hirtz\Skeleton\Html\A;
use Stringable;

trait LinkTrait
{
    protected ?Closure $link = null;

    /**
     * @param Closure(A):(string|Stringable)|null $link
     * @return $this
     */
    public function link(?Closure $link): static
    {
        $this->link = $link;
        return $this;
    }

    protected function getLink(A $link): string|Stringable|null
    {
        return $this->link ? ($this->link)($link) : $link;
    }
}
