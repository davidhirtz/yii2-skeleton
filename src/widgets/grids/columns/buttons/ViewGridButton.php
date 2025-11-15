<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\html\Button;

class ViewGridButton extends GridButton
{
    public function renderContent(): string
    {
        if ($this->model) {
            $this->href ??= ['update', 'id' => $this->model->getPrimaryKey()];
        }

        return Button::make()
            ->primary()
            ->icon($this->icon ?? 'wrench')
            ->href($this->href)
            ->addClass('d-none d-md-block')
            ->render();
    }
}
