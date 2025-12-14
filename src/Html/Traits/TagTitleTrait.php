<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Hirtz\Skeleton\Helpers\Html;
use Stringable;

trait TagTitleTrait
{
    protected string|Stringable|false|null $title = null;

    public function title(string|Stringable|false|null $title): static
    {
        $this->title = is_string($title) ? Html::encode($title) : $title;
        return $this;
    }
}
