<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\DeleteForm;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Traits\PropertyTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Override;
use Yii;

class DeleteActiveForm extends ActiveForm
{
    use PropertyTrait;
    use LabelTrait;

    public array $inputAttributes = ['autocomplete' => 'off'];
    public bool $hasStickyButtons = false;

    protected ?string $message = null;
    protected string|false|null $confirm = null;

    public function inputAttributes(array $attributes): static
    {
        $this->inputAttributes = $attributes;
        return $this;
    }

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

    #[Override]
    protected function configure(): void
    {
        $form = DeleteForm::create([
            'model' => $this->model,
            'attribute' => $this->property,
        ]);

        $this->model = $form;

        $this->message ??= $this->property
            ? Yii::t('skeleton', 'COMMON_TYPE_EXACT', [
                'attribute' => $form->getAttributeLabel('value'),
            ])
            : Yii::t('skeleton', 'DELETE_ACTIVE_WARNING_DELETED');

        $this->action ??= ['delete', 'id' => $form->getId()];

        $this->confirm ??= Yii::t('yii', 'Are you sure you want to delete this item?');
        $this->label ??= Yii::t('skeleton', 'DELETE_ACTIVE_DELETE');

        $btn = Button::make()
            ->danger()
            ->attribute('form', $this->getId())
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

        $expected = $form->getExpectedValue();

        $this->rows ??= [
            FormRow::make()
                ->content($this->message),
            $form->attribute
                ? InputField::make()
                ->attributes($this->inputAttributes)
                ->pattern($expected !== null ? '^' . preg_quote($expected, '/') . '$' : null)
                ->property('value')
                ->required()
                : null,
        ];

        parent::configure();
    }
}
