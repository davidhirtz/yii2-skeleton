<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\traits;

trait TagCardTrait
{
    use TagCollapsedTrait;
    use TagContentTrait;
    use TagTitleTrait;

    public function danger(): static
    {
        return $this->addClass('card-danger');
    }
}
