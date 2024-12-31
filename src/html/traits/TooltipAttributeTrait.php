<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

trait TooltipAttributeTrait
{
    public function tooltip(string $tooltip): self
    {
        $new = clone $this;
        $new->attributes['data-toggle'] = 'tooltip';
        $new->attributes['title'] = $tooltip;
        return $new;
    }
}
