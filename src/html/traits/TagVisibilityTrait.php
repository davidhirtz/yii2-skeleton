<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

trait TagVisibilityTrait
{
    private bool $visible = true;

    public function visible(callable|bool $visible): static
    {
        $this->visible = is_callable($visible) ? $visible() : $visible;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }
}
