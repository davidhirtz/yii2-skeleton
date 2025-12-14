<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

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
