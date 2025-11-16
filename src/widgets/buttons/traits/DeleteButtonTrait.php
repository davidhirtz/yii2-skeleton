<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\buttons\traits;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use Yii;

trait DeleteButtonTrait
{
    use ModelWidgetTrait;
    use TagUrlTrait;
    use TagIconTextTrait;
    use TagLabelTrait;
    use TagTitleTrait;

    public function setButtonDefault(): void
    {
        if ($this->model) {
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