<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

class DeleteButton extends Widget
{
    public ?string $icon = 'trash';
    public ?string $message = null;
    public ?ActiveRecord $model = null;
    public array $options = [];
    public array|string $url;
    public string $target;

    public function init(): void
    {
        $this->message ??= Yii::t('yii', 'Are you sure you want to delete this item?');

        if ($this->model) {
            $this->url ??= ['delete', 'id' => $this->model->getPrimaryKey()];
            $this->target ??= '#' . Inflector::camel2id($this->model->formName()) . '-' . $this->model->getPrimaryKey();
        }

        parent::init();
    }

    public function render(): string
    {
        $modal = Modal::make()
            ->title($this->message)
            ->footer(Button::danger()
                ->text(Yii::t('yii', 'Delete'))
                ->delete($this->url, $this->target));

        return Button::danger()
            ->icon($this->icon)
            ->modal($modal)
            ->addAttributes($this->options)
            ->render();
    }
}
