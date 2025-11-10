<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\buttons;

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
        private readonly ?string $target = null,
    ) {
        $this->message ??= Yii::t('yii', 'Are you sure you want to delete this item?');

        if ($this->model) {
            $this->url ??= ['delete', 'id' => $this->model->getPrimaryKey()];
        }
    }

    public function render(): string
    {
        $modal = Modal::make()
            ->title($this->message)
            ->footer(Button::danger()
                ->post($this->url, $this->target)
                ->text(Yii::t('yii', 'Delete')));

        return Button::danger()
            ->icon($this->icon)
            ->modal($modal)
            ->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
