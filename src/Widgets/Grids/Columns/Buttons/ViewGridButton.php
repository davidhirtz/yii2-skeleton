<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns\Buttons;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Traits\TagIconTextTrait;
use Hirtz\Skeleton\Html\Traits\TagLabelTrait;
use Hirtz\Skeleton\Html\Traits\TagTooltipAttributeTrait;
use Hirtz\Skeleton\Html\Traits\TagUrlTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
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
