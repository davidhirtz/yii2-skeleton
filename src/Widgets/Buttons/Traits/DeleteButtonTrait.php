<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons\Traits;

use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Traits\IconTextTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Yii;
use yii\db\ActiveRecordInterface;

trait DeleteButtonTrait
{
    use ModelTrait;
    use UrlTrait;
    use IconTextTrait;
    use LabelTrait;
    use TitleTrait;

    public function setButtonDefault(): void
    {
        if ($this->model instanceof ActiveRecordInterface) {
            $this->url ??= [
                'delete',
                ...Yii::$app->getRequest()->getQueryParams(),
                'id' => $this->model->getPrimaryKey(),
            ];
        }

        $this->icon ??= 'trash';

        $this->label ??= Yii::t('yii', 'Delete');
        $this->title ??= Yii::t('yii', 'Are you sure you want to delete this item?');
    }
}
