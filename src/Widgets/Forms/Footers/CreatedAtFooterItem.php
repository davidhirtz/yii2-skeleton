<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Footers;

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

    public string $attributeName = 'created_at';

    protected function getItem(): ?Li
    {
        $createdAt = in_array($this->attributeName, $this->model->attributes(), true)
            ? $this->model->{$this->attributeName}
            : null;

        return $createdAt
            ? Li::make()
                ->class('form-footer-item')
                ->content(Yii::t('skeleton', 'Created {timestamp}', [
                    'timestamp' => RelativeTime::make()->value($createdAt),
                ]))
            : null;
    }

    public function __toString(): string
    {
        return (string)$this->getItem();
    }
}
