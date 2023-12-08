<?php

namespace davidhirtz\yii2\skeleton\base\traits;

use ArrayObject;
use Yii;

trait ModelTrait
{
    private ?array $_activeAttributes = null;
    private ?array $_safeAttributes = null;
    private ?array $_scenarios = null;
    private ?ArrayObject $_validators = null;

    public function addInvalidAttributeError(string $attribute): bool
    {
        $this->addError($attribute, Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $this->getAttributeLabel($attribute),
        ]));

        return false;
    }

    /**
     * This method is in place to avoid endless calls to {@see \yii\db\ActiveRecord::activeAttributes()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     */
    public function activeAttributes(): array
    {
        $this->_activeAttributes ??= parent::activeAttributes();
        return $this->_activeAttributes;
    }

    /**
     * This method is in place to avoid excessive calls to {@see \yii\db\ActiveRecord::safeAttributes()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     */
    public function safeAttributes(): array
    {
        $this->_safeAttributes ??= parent::safeAttributes();
        return $this->_safeAttributes;
    }

    /**
     * This method is in place to avoid endless calls to {@see \yii\db\ActiveRecord::scenarios()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     */
    public function scenarios(): array
    {
        $this->_scenarios ??= parent::scenarios();
        return $this->_scenarios;
    }

    public function setScenario($value): void
    {
        $this->_activeAttributes = null;
        $this->_safeAttributes = null;
        $this->_scenarios = null;

        parent::setScenario($value);
    }

    /**
     * Overrides original method by triggering {@see static::EVENT_CREATE_VALIDATORS} event. This enables attached
     * behaviors to manipulate {@see Model::rules()} by modifying the array object returned by
     * {@see Model::getValidators()}.
     *
     * This would be more fitting in {@see Model::rules()}. I might add a pull request... If this is added to Yii2, the
     * override can be removed. {@link https://github.com/yiisoft/yii2/issues/5438}
     */
    public function getValidators(): ArrayObject
    {
        if ($this->_validators === null) {
            $this->_validators = $this->createValidators();
            $this->trigger(static::EVENT_CREATE_VALIDATORS);
        }

        return $this->_validators;
    }

    public static function create(): static
    {
        return Yii::createObject(static::class);
    }
}