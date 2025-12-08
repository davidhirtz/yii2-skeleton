<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids\columns\buttons;

use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\html\traits\TagIconTextTrait;
use Hirtz\Skeleton\html\traits\TagLabelTrait;
use Hirtz\Skeleton\html\traits\TagUrlTrait;
use Hirtz\Skeleton\widgets\traits\ModelWidgetTrait;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;

class ViewGridButton extends Widget
{
    use ModelWidgetTrait;
    use TagUrlTrait;
    use TagIconTextTrait;
    use TagLabelTrait;

    public function renderContent(): Stringable
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
