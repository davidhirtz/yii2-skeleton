<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Hirtz\Skeleton\Helpers\Html;
use Stringable;

trait TagLabelTrait
{
    protected ?string $label = null;

    public function label(string|Stringable|null $label): static
    {
        $this->label = is_string($label) ? Html::encode($label) : null;
        return $this;
    }
}
