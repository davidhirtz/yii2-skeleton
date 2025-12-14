<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

trait TagPlaceholderTrait
{
    public function placeholder(?string $placeholder = null): static
    {
        return $this->attribute('placeholder', $placeholder);
    }
}
