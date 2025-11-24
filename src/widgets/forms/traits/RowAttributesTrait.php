<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\traits;

trait RowAttributesTrait
{

    public array $rowAttributes = [];

    public function rowAttributes(array $attributes): static
    {
        $this->rowAttributes = $attributes;
        return $this;
    }
}