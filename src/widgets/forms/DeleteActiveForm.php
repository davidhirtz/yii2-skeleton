<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use Yii;
use yii\db\ActiveRecord;

class DeleteActiveForm extends ActiveForm
{
    public string|false $attribute = false;

    public ?string $message = null;
    public ?string $label = null;
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

        $this->buttons ??= [
            $this->button($this->label, [
                'class' => 'btn-danger',
                'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
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

    /**
     * @noinspection PhpUnused
     */
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
