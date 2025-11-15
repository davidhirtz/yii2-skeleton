<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\html\Button;

class ViewButton extends GridButton
{
    public function renderContent(): string
    {
        if ($this->model) {
            $this->url ??= ['update', 'id' => $this->model->getPrimaryKey()];
        }

        return Button::make()
            ->primary()
            ->icon($this->icon ?? 'wrench')
            ->href($this->url)
            ->addClass('d-none d-md-block')
            ->render();
    }
}
