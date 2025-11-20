<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\Modal;
use davidhirtz\yii2\skeleton\widgets\traits\PropertyWidgetTrait;
use Stringable;
use Yii;
use yii\db\ActiveRecord;

class DeleteActiveForm extends ActiveForm
{
    use PropertyWidgetTrait;
    use TagLabelTrait;

    public bool $hasStickyButtons = false;

    protected ?string $message = null;
    protected string|false|null $confirm = null;

    public function message(string|null $message): static
    {
        $this->message = $message;
        return $this;
    }


    public function confirm(string|false|null $confirm): static
    {
        $this->confirm = $confirm;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $this->message ??= $this->property
            ? Yii::t('skeleton', 'Please type the exact {attribute} in the text field below to delete this record. All related files will also be unrecoverably deleted. This cannot be undone, please be certain!', [
                'attribute' => $this->model->getAttributeLabel($this->property),
            ])
            : Yii::t('skeleton', 'Warning: Deleting this record cannot be undone. All related files will also be unrecoverably deleted. Please be certain!');

        if ($this->model instanceof ActiveRecord) {
            $this->action ??= ['delete', 'id' => $this->model->getPrimaryKey()];
        }

        $this->confirm ??= Yii::t('yii', 'Are you sure you want to delete this item?');
        $this->label ??= Yii::t('skeleton', 'Delete');

        $btn = Button::make()
            ->danger()
            ->text($this->label);

        if ($this->confirm) {
            $modal = Modal::make()
                ->title($this->confirm)
                ->footer(Button::make()
                    ->danger()
                    ->text($this->label)
                    ->type('submit')
                    ->attribute('form', $this->getId()));

            $btn->modal($modal);
        }

        $this->buttons ??= [$btn];
        $this->footer ??= false;

        $this->rows ??= [
            FormRow::make()
                ->content($this->message),
            InputField::make()
                ->property($this->property)
                ->value('')
                ->required()
                ->visible($this->property !== null)
                ->required(),
        ];

        return parent::renderContent();
    }
}
