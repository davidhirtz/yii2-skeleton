<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\buttons\traits;

use Hirtz\Skeleton\html\Icon;
use Hirtz\Skeleton\html\traits\TagIconTextTrait;
use Hirtz\Skeleton\html\traits\TagLabelTrait;
use Hirtz\Skeleton\html\traits\TagTitleTrait;
use Hirtz\Skeleton\html\traits\TagUrlTrait;
use Hirtz\Skeleton\widgets\traits\ModelWidgetTrait;
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
