<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\traits;

use Yii;

trait FormatTrait
{
    protected string $format = 'text';

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    protected function formatValue(mixed $value): string
    {
        return Yii::$app->getFormatter()->format($value, $this->format);
    }
}
