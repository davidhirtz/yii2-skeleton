<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\web\NotFoundHttpException;

/**
 * Class DeleteForm.
 * @package davidhirtz\yii2\skeleton\models\forms
 */
class DeleteForm extends Model
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $attribute = 'name';

    /**
     * @var \davidhirtz\yii2\skeleton\db\ActiveRecord {@link DeleteForm::getModel()}
     */
    private $_model;

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [
                ['value'],
                'required',
                'when' => function () {
                    return $this->attribute;
                }
            ],
            [
                ['value'],
                /** {@link DeleteForm::validateName()} */
                'validateName',
            ],
        ];
    }

    /**
     * Checks for a validation method on parent model or compares the given value with the
     * model attribute.
     */
    public function validateName()
    {
        $methodName = 'validate' . Inflector::camelize($this->attribute);
        $model = $this->getModel();

        if (method_exists($model, $methodName) ? !call_user_func([$model, $methodName], $this->value) : $this->value !== $model->getAttribute($this->attribute)) {
            $this->addError('name', Yii::t('yii', '{attribute} is invalid.', [
                'attribute' => $this->getModel()->getAttributeLabel($this->attribute),
            ]));
        }
    }

    /**
     * @return bool
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    public function delete()
    {
        if ($this->validate()) {
            if (!$this->getModel()->delete()) {
                $this->addErrors($this->getModel()->getErrors());
            }

        }

        return !$this->hasErrors();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->getModel()->getPrimaryKey();
    }

    /**
     * @return \davidhirtz\yii2\skeleton\db\ActiveRecord
     */
    public function getModel()
    {
        if (!$this->_model) {
            throw new InvalidConfigException;
        }

        return $this->_model;
    }

    /**
     * @param \davidhirtz\yii2\skeleton\db\ActiveRecord $model
     */
    public function setModel($model)
    {
        if (!$model) {
            throw new NotFoundHttpException;
        }

        $this->_model = $model;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'value' => $this->getModel()->getAttributeLabel($this->attribute),
        ];
    }
}