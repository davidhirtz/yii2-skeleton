<?php

namespace davidhirtz\yii2\skeleton\html\traits;

trait ConditionalRenderTrait
{
    private bool $visible = true;

    public function visible(callable|bool $visible): self
    {
        $new = clone $this;
        $new->visible = is_callable($visible) ? $visible() : $visible;
        return $new;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }
}
