<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;

/**
 * Class DeleteActiveForm.
 * @package davidhirtz\yii2\skeleton\widgets\forms
 */
class DeleteActiveForm extends ActiveForm
{
    /**
     * @var \davidhirtz\yii2\skeleton\db\ActiveRecord
     */
    public $model;

    /**
     * @var string
     */
    public $attribute = false;

    /**
     * @var string
     */
    public $message;

    /**
     * @var array|string
     */
    public $action;

    /**
     * @var array|string
     */
    public $label;

    /**
     * @var \davidhirtz\yii2\skeleton\models\forms\DeleteForm
     */
    private $_form;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->action === null) {
            $this->action = ['delete', 'id' => $this->model->getPrimaryKey()];
        }

        if(!$this->label) {
            $this->label = Yii::t('skeleton', 'Delete');
        }

        if ($this->message === null) {
            if ($this->attribute) {
                $this->message = Yii::t('skeleton', 'Please type the exact {attribute} in the text field below to delete this record. All related files will also be unrecoverably deleted. This cannot be undone, please be certain!', [
                    '{attribute}' => $this->model->getAttributeLabel($this->attribute),
                ]);
            } else {
                $this->message = Yii::t('skeleton', 'Warning: Deleting this record cannot be undone. All related files will also be unrecoverably deleted. Please be certain!');
            }
        }

        if ($this->buttons === null) {
            $this->buttons = [
                $this->button($this->label, [
                    'class' => 'btn-danger',
                    'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                ])
            ];
        }

        parent::init();
    }

    /**
     * @return DeleteForm
     */
    public function getForm()
    {
        if ($this->_form === null) {
            $this->_form = new DeleteForm([
                'model' => $this->model,
                'attribute' => $this->attribute,
            ]);
        }

        return $this->_form;
    }

    /**
     * @param Model $form
     */
    public function setForm($form)
    {
        $this->_form = $form;
    }

    /**
     * @inheritdoc
     */
    public function renderFields()
    {
        if ($this->message) {
            echo $this->textRow($this->message);
        }

        if ($this->attribute) {
            echo $this->field($this->getForm(), 'name');
        }
    }
}