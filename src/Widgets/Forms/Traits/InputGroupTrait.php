<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Traits;

use Stringable;

trait InputGroupTrait
{
    /**
     * @var list<string|Stringable>
     */
    protected array $append = [];

    /**
     * @var list<string|Stringable>
     */
    protected array $prepend = [];

    public function append(string|Stringable|null ...$content): static
    {
        $this->append = array_values(array_filter($content));
        return $this;
    }

    public function prepend(string|Stringable|null ...$content): static
    {
        $this->prepend = array_values(array_filter($content));
        return $this;
    }
}
