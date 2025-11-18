<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\footers;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\html\Li;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\timeago\Timeago;
use Stringable;
use Yii;

class CreatedAtFooterItem implements Stringable
{
    use ContainerConfigurationTrait;
    use ModelWidgetTrait;

    public string $attributeName = 'created_at';

    protected function getItem(): ?Li
    {
        $createdAt = in_array($this->attributeName, $this->model->attributes())
            ? $this->model->{$this->attributeName}
            : null;

        return $createdAt
            ? Li::make()
                ->class('form-footer-item')
                ->content(Yii::t('skeleton', 'Created {timestamp}', [
                    'timestamp' => Timeago::tag($createdAt),
                ]))
            : null;
    }

    public function __toString(): string
    {
        return (string)$this->getItem();
    }
}