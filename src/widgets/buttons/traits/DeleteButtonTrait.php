<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\buttons\traits;

use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use Yii;

trait DeleteButtonTrait
{
    use TagTitleTrait;

    public function setButtonDefault(): void
    {
        $this->icon ??= Icon::make()
            ->name('trash');

        $this->label ??= Yii::t('yii', 'Delete');
        $this->title ??= Yii::t('yii', 'Are you sure you want to delete this item?');

        if ($this->model) {
            $this->href ??= [
                'delete',
                ...Yii::$app->getRequest()->getQueryParams(),
                'id' => $this->model->getPrimaryKey(),
            ];
        }
    }
}