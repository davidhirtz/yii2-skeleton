<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\custom;

use DateTimeInterface;
use davidhirtz\yii2\skeleton\html\base\Tag;
use Override;
use Stringable;
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
        $this->attributes['data-date'] ??= Yii::$app->getFormatter()->asDatetime($this->value, "php:c");
        return parent::before();
    }

    #[Override]
    protected function getTag(): string
    {
        return null !== $this->value ? parent::getTag() : '';
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Yii::$app->getFormatter()->asDatetime($this->value);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'x-timeago';
    }
}
