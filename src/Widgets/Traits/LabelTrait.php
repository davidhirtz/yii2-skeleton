<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

trait LabelTrait
{
    protected ?string $label = null;

    public function label(string|null $label): static
    {
        $this->label = $label;
        return $this;
    }
}
