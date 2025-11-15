<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use Stringable;

trait TagLabelTrait
{
    protected ?string $label = null;

    public function label(string|Stringable|null $label): static
    {
        $this->label = is_string($label) ? Html::encode($label) : null;
        return $this;
    }
}
