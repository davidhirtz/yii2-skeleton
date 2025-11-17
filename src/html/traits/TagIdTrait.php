<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Html;

trait TagIdTrait
{
    final public function getId(): string
    {
        return $this->attributes['id'] ??= Html::getId();
    }
}
