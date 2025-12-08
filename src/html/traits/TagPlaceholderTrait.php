<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\traits;

trait TagPlaceholderTrait
{
    public function placeholder(?string $placeholder = null): static
    {
        return $this->attribute('placeholder', $placeholder);
    }
}
