<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

trait TooltipAttributeTrait
{
    public function tooltip(string $tooltip): static
    {
        return $this->addAttributes([
            'data-tooltip' => '',
            'title' => $tooltip,
        ]);
    }
}
