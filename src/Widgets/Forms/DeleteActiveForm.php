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

    /**
     * @var array<string, mixed>
     */
    public array $inputAttributes = ['autocomplete' => 'off'];
    public bool $hasStickyButtons = false;

    protected ?string $message = null;
    protected string|false|null $confirm = null;
    protected DeleteForm $deleteForm;

    /**
     * @param array<string, mixed> $attributes
     */
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
        $this->deleteForm = DeleteForm::create([
            'model' => $this->model,
            'attribute' => $this->property,
        ]);

        $this->model = $this->deleteForm;

        $this->message ??= $this->property
            ? Yii::t('skeleton', 'COMMON_TYPE_EXACT', [
                'attribute' => $this->deleteForm->getAttributeLabel('value'),
            ])
            : Yii::t('skeleton', 'DELETE_ACTIVE_WARNING_DELETED');

        $this->action ??= ['delete', 'id' => $this->deleteForm->getId()];

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

        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        $expected = $this->deleteForm->getExpectedValue();

        return [
            FormRow::make()
                ->content($this->message),
            $this->deleteForm->attribute
                ? InputField::make()
                    ->attributes($this->inputAttributes)
                    ->pattern($expected !== null ? '^' . preg_quote($expected, '/') . '$' : null)
                    ->property('value')
                    ->required()
                : null,
        ];
    }
}
