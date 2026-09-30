<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Stringable;

trait TitleTrait
{
    protected string|Stringable|false|null $title = null;

    public function title(string|Stringable|false|null $title): static
    {
        $this->title = $title;
        return $this;
    }
}
