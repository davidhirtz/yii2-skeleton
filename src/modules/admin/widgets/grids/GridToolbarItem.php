<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use Stringable;

readonly class GridToolbarItem implements Stringable
{
    public function __construct(
        public string|Stringable $content,
        public array $attributes = [],
        public bool $visible = true,
    ) {
    }

    public function __toString(): string
    {
        return Html::div($this->content, $this->attributes)->render();
    }
}
