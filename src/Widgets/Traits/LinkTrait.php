<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Closure;
use Hirtz\Skeleton\Html\A;

trait LinkTrait
{
    /**
     * @var list<Closure>|null
     */
    private ?array $linkClosures = null;

    /**
     * @param Closure(A): A $link
     */
    public function link(Closure $link): static
    {
        $this->linkClosures[] = $link;
        return $this;
    }

    protected function getLink(A $link): A
    {
        return $this->evaluate($this->linkClosures, $link);
    }
}
