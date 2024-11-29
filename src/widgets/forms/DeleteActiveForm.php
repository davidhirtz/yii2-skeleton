<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

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

    /**
     * @var string|null the message to display above the "delete" button, defaults to a generic warning message
     */
    public ?string $message = null;

    /**
     * @var string|null the label of the "delete" button, defaults to "Delete"
     */
    public ?string $label = null;

    /**
     * @var string|null the confirmation message to display when the "delete" button is clicked, defaults to generic
     * confirmation message
     */
    public ?string $confirm = null;

    /**
     * @var array the options for the "delete" text field
     */
    public array $fieldOptions = [];

    private ?DeleteForm $_form = null;

    public function init(): void
    {
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

        $this->buttons ??= [
            $this->button($this->label, [
                'class' => 'btn-danger',
                'data-confirm' => $this->confirm,
            ])
        ];

        parent::init();
    }

    public function getForm(): DeleteForm
    {
        $this->_form ??= Yii::$container->get(DeleteForm::class, [], [
            'model' => $this->model,
            'attribute' => $this->attribute,
        ]);

        return $this->_form;
    }

    public function setForm(DeleteForm $form): void
    {
        $this->_form = $form;
    }

    public function renderFields(): void
    {
        if ($this->message) {
            echo $this->textRow($this->message);
        }

        if ($this->attribute) {
            echo $this->field($this->getForm(), 'value', $this->fieldOptions);
        }
    }
}
