<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

trait TagPlaceholderTrait
{
    public function placeholder(?string $placeholder): static
    {
        return $this->attribute('placeholder', $placeholder);
    }
}
