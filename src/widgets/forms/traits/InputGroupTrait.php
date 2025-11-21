<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\traits;

use Stringable;

trait InputGroupTrait
{
    protected array $append = [];
    protected array $prepend = [];

    public function append(string|Stringable|null ...$content): static
    {
        $this->append = array_filter($content);
        return $this;
    }

    public function prepend(string|Stringable|null ...$content): static
    {
        $this->prepend = array_filter($content);
        return $this;
    }
}
