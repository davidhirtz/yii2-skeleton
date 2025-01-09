<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

trait TagTooltipAttributeTrait
{
    public function tooltip(string $tooltip): static
    {
        return $this->addAttributes([
            'data-tooltip' => '',
            'title' => $tooltip,
        ]);
    }
}
