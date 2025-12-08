<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms\traits;

use Stringable;

trait InputGroupTrait
{
    protected string|Stringable|null $append = null;
    protected string|Stringable|null $prepend = null;

    public function append(string|Stringable|null $content): static
    {
        $this->append = $content;
        return $this;
    }

    public function prepend(string|Stringable|null $content): static
    {
        $this->prepend = $content;
        return $this;
    }
}
