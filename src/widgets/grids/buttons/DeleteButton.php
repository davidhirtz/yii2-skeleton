<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use Stringable;
use Yii;
use yii\db\ActiveRecord;

class DeleteButton implements Stringable
{
    public function __construct(
        private readonly ?ActiveRecord $model = null,
        private array|null|string $url = null,
        private ?string $message = null,
        private readonly ?string $icon = 'trash',
    ) {
        $this->message ??= Yii::t('yii', 'Are you sure you want to delete this item?');

        if ($this->model) {
            $this->url ??= [
                'delete',
                ...Yii::$app->getRequest()->getQueryParams(),
                'id' => $this->model->getPrimaryKey(),
            ];
        }
    }

    public function __toString(): string
    {
        $modal = Modal::make()
            ->title($this->message)
            ->footer(Button::make()
                ->danger()
                ->post($this->url)
                ->text(Yii::t('yii', 'Delete')));

        return Button::make()
            ->danger()
            ->icon($this->icon)
            ->modal($modal)
            ->render();
    }
}
