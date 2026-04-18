<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns\Buttons;

use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Traits\IconTextTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;

class ViewGridButton extends Widget
{
    use ModelTrait;
    use UrlTrait;
    use IconTextTrait;
    use LabelTrait;

    protected function renderContent(): Stringable
    {
        if ($this->model instanceof ActiveRecordInterface) {
            $this->url ??= ['update', 'id' => $this->model->getPrimaryKey()];
        }

        $this->label ??= Yii::t('yii', 'View');

        return Button::make()
            ->primary()
            ->ariaLabel($this->label)
            ->icon($this->icon ?? 'wrench')
            ->href($this->url)
            ->addClass('hidden md:block');
    }
}
