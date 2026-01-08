<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Footers;

use DateTimeInterface;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Stringable;
use Yii;

class CreatedAtFooterItem implements Stringable
{
    use ContainerConfigurationTrait;
    use ModelWidgetTrait;

    protected string $attributeName = 'created_at';
    protected DateTimeInterface|int|string|null $value = null;

    public function value(DateTimeInterface|int|string|null $value): static
    {
        $this->value = $value;
        return $this;
    }

    protected function getItem(): ?Li
    {
        $this->value ??= in_array($this->attributeName, $this->model->attributes(), true)
            ? $this->model->{$this->attributeName}
            : null;

        return $this->value
            ? Li::make()
                ->class('form-footer-item')
                ->content(Yii::t('skeleton', 'Created {timestamp}', [
                    'timestamp' => RelativeTime::make()->value($this->value),
                ]))
            : null;
    }

    public function __toString(): string
    {
        return (string)$this->getItem();
    }
}
