<?php
declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\html\Icon;

trait TagIconTrait
{
    protected ?Icon $icon = null;

    public function icon(string|Icon|null $icon): static
    {
        $this->icon = is_string($icon) ? Icon::make()->name($icon) : $icon;
        return $this;
    }
}
