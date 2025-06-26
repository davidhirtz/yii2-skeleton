<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\web\NotFoundHttpException;

class DeleteForm extends Model
{
    public ?string $value = null;
    public string $attribute = 'name';

    private ?ActiveRecord $_model = null;

    #[\Override]
    public function rules(): array
    {
        return [
            [
                ['value'],
                'required',
                'when' => fn () => $this->attribute
            ],
            [
                ['value'],
                $this->validateName(...),
            ],
        ];
    }

    /**
     * Checks for a validation method on the parent model or compares the given value with the model attribute.
     */
    public function validateName(): void
    {
        $methodName = 'validate' . Inflector::camelize($this->attribute);
        $model = $this->getModel();

        $isValid = method_exists($model, $methodName)
            ? !call_user_func([$model, $methodName], $this->value)
            : $this->value !== $model->getAttribute($this->attribute);

        if ($isValid) {
            $this->addError('name', Yii::t('yii', '{attribute} is invalid.', [
                'attribute' => $this->getModel()->getAttributeLabel($this->attribute),
            ]));
        }
    }

    public function delete(): bool
    {
        if ($this->validate()) {
            if (!$this->getModel()->delete()) {
                $this->addErrors($this->getModel()->getErrors());
            }
        }

        return !$this->hasErrors();
    }

    public function getId(): array|int|string
    {
        return $this->getModel()->getPrimaryKey();
    }

    public function getModel(): ?ActiveRecord
    {
        if (!$this->_model) {
            throw new InvalidConfigException();
        }

        return $this->_model;
    }

    public function setModel(?ActiveRecord $model): void
    {
        if (!$model) {
            throw new NotFoundHttpException();
        }

        $this->_model = $model;
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'value' => $this->getModel()->getAttributeLabel($this->attribute),
        ];
    }
}
