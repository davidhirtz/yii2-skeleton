<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property DeleteForm $form {@see static::setForm()}
 */
class DeleteActiveForm extends ActiveForm
{
    public string|false $attribute = false;
    public DeleteForm $form;

    /**
     * @var string|null the message to display above the "delete" button, defaults to a generic warning message
     */
    public ?string $message = null;

    /**
     * @var string|null the label of the "delete" button, defaults to "Delete"
     */
    public ?string $label = null;

    /**
     * @var string|false|null the confirmation message to display when the "delete" button is clicked, defaults to a
     * generic confirmation message, set to `false` to disable confirmation
     */
    public string|false|null $confirm = null;

    /**
     * @var array the options for the "delete" text field
     */
    public array $fieldOptions = [];

    public function init(): void
    {
        $this->form ??= Yii::$container->get(DeleteForm::class, [], [
            'model' => $this->model,
            'attribute' => $this->attribute,
        ]);

        if ($this->action === '' && $this->model instanceof ActiveRecord) {
            $this->action = ['delete', 'id' => $this->model->getPrimaryKey()];
        }

        $this->label ??= Yii::t('skeleton', 'Delete');

        if ($this->attribute) {
            $this->message ??= Yii::t('skeleton', 'Please type the exact {attribute} in the text field below to delete this record. All related files will also be unrecoverably deleted. This cannot be undone, please be certain!', [
                'attribute' => $this->model->getAttributeLabel($this->attribute),
            ]);
        } else {
            $this->message ??= Yii::t('skeleton', 'Warning: Deleting this record cannot be undone. All related files will also be unrecoverably deleted. Please be certain!');
        }

        $this->confirm ??= Yii::t('yii', 'Are you sure you want to delete this item?');

        $btn = Button::danger($this->label)
            ->type('submit');

        if ($this->confirm) {
            $modal = Modal::make()
                ->title($this->confirm)
                ->footer(Button::danger($this->label)
                    ->type('submit')
                    ->attribute('form', $this->getId()));

            $btn->modal($modal);
        }

        $this->buttons ??= $btn->render();

        parent::init();
    }

    public function renderFields(): void
    {
        if ($this->message) {
            echo $this->textRow($this->message);
        }

        if ($this->attribute) {
            echo $this->field($this->form, 'value', $this->fieldOptions);
        }
    }
}
