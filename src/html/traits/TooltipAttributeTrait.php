<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Html;

trait TooltipAttributeTrait
{
    public function tooltip(string $tooltip): self
    {
        $new = clone $this;
        Html::addTooltip($new->attributes['data-toggle'], $tooltip);
        return $new;
    }
}
