<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Hirtz\Skeleton\Helpers\Html;

trait TagIdTrait
{
    final public function getId(): string
    {
        return $this->attributes['id'] ??= Html::getId();
    }
}
