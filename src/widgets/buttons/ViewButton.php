<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\widgets\Widget;
use yii\db\ActiveRecord;

class ViewButton extends Widget
{
    public ?string $icon = 'wrench';
    public ?ActiveRecord $model = null;
    public array $options = [];
    public array|string $url;

    public function init(): void
    {
        if ($this->model) {
            $this->url ??= ['update', 'id' => $this->model->getPrimaryKey()];
        }

        parent::init();
    }

    public function render(): string
    {
        return Button::primary()
            ->icon($this->icon)
            ->href($this->url)
            ->addAttributes($this->options)
            ->render();
    }
}
