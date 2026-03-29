<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;

trait CardTrait
{
    use CollapsedTrait;
    use TagContentTrait;
    use TitleTrait;

    public function danger(): static
    {
        return $this->attribute('data-card', 'danger');
    }
}
