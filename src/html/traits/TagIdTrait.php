<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\traits;

use Hirtz\Skeleton\helpers\Html;

trait TagIdTrait
{
    final public function getId(): string
    {
        return $this->attributes['id'] ??= Html::getId();
    }
}
