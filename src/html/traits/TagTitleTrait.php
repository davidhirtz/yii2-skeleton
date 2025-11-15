<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use Stringable;

trait TagTitleTrait
{
    protected ?string $title = null;

    public function title(string|Stringable|null $title): static
    {
        $this->title = is_string($title) ? Html::encode($title) : null;
        return $this;
    }

}