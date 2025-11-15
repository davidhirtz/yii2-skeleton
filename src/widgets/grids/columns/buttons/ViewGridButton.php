<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;

class ViewGridButton extends Widget
{
    use ModelWidgetTrait;
    use TagUrlTrait;
    use TagIconTextTrait;
    use TagLabelTrait;

    public function init(): void
    {
        if ($this->model) {
            $this->url ??= ['update', 'id' => $this->model->getPrimaryKey()];
        }

        $this->label ??= Yii::t('yii', 'View');

        parent::init();
    }

    public function renderContent(): Stringable
    {
        return Button::make()
            ->primary()
            ->ariaLabel($this->label)
            ->icon($this->icon ?? 'wrench')
            ->href($this->url)
            ->addClass('d-none d-md-block');
    }
}
