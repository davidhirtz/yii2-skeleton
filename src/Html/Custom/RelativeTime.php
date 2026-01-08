<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Custom;

use DateTimeInterface;
use Hirtz\Skeleton\Html\Base\Tag;
use Override;
use Yii;

class RelativeTime extends Tag
{
    protected DateTimeInterface|int|string|null $value = null;

    public function value(DateTimeInterface|int|string|null $value): static
    {
        $this->value = $value;
        return $this;
    }

    #[Override]
    protected function before(): string
    {
        $this->attributes['date'] ??= Yii::$app->getFormatter()->asDatetime($this->value, "php:c");
        return parent::before();
    }

    #[Override]
    protected function getTag(): string
    {
        return null !== $this->value ? parent::getTag() : '';
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'x-timeago';
    }
}
