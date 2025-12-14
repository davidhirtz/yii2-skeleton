<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons\Traits;

use Hirtz\Skeleton\Html\Icon;
use Hirtz\Skeleton\Html\Traits\TagIconTextTrait;
use Hirtz\Skeleton\Html\Traits\TagLabelTrait;
use Hirtz\Skeleton\Html\Traits\TagTitleTrait;
use Hirtz\Skeleton\Html\Traits\TagUrlTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Yii;
use yii\db\ActiveRecordInterface;

trait DeleteButtonTrait
{
    use ModelWidgetTrait;
    use TagUrlTrait;
    use TagIconTextTrait;
    use TagLabelTrait;
    use TagTitleTrait;

    public function setButtonDefault(): void
    {
        if ($this->model instanceof ActiveRecordInterface) {
            $this->url ??= [
                'delete',
                ...Yii::$app->getRequest()->getQueryParams(),
                'id' => $this->model->getPrimaryKey(),
            ];
        }

        $this->icon ??= Icon::make()
            ->name('trash');

        $this->label ??= Yii::t('yii', 'Delete');
        $this->title ??= Yii::t('yii', 'Are you sure you want to delete this item?');
    }
}
