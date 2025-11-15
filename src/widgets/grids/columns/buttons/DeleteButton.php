<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use Yii;

class DeleteButton extends GridButton
{
    protected ?string $message = null;

    public function render(): string
    {
        $this->message ??= Yii::t('yii', 'Are you sure you want to delete this item?');

        if ($this->model) {
            $this->url ??= [
                'delete',
                ...Yii::$app->getRequest()->getQueryParams(),
                'id' => $this->model->getPrimaryKey(),
            ];
        }

        $modal = Modal::make()
            ->title($this->message)
            ->footer(Button::make()
                ->danger()
                ->post($this->url)
                ->text($this->label ?? Yii::t('yii', 'Delete')));

        return Button::make()
            ->danger()
            ->icon($this->icon ?? 'trash')
            ->modal($modal)
            ->render();
    }
}
