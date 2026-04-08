<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class Badge extends Widget
{
    use TagAttributesTrait;

    protected ?string $value = null;

    public function value(string|int|float|null $value): static
    {
        $this->value = is_numeric($value) ? Yii::$app->getFormatter()->asInteger($value) : $value;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        return Span::make()
            ->attributes($this->attributes)
            ->addClass('badge')
            ->text($this->value);
    }
}
