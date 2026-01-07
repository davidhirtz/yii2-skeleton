<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Stringable;
use Yii;

trait FormatTrait
{
    protected string $format = 'text';

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    protected function formatValue(mixed $value): string|Stringable
    {
        return Yii::$app->getFormatter()->format($value, $this->format);
    }
}
