<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use Stringable;
use yii\db\ActiveRecord;

class ViewButton implements Stringable
{
    public function __construct(
        private readonly ?ActiveRecord $model = null,
        private array|null|string $url = null,
        private readonly ?string $icon = 'wrench',
    ) {
        if ($this->model) {
            $this->url ??= ['update', 'id' => $this->model->getPrimaryKey()];
        }
    }

    public function __toString(): string
    {
        return Button::primary()
            ->icon($this->icon)
            ->href($this->url)
            ->addClass('d-none d-md-block')
            ->render();
    }
}
