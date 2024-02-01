<?php

namespace davidhirtz\yii2\skeleton\base\traits;

use ArrayObject;
use davidhirtz\yii2\skeleton\models\events\CreateValidatorsEvent;
use ReflectionClass;
use Yii;

trait ModelTrait
{
    private ?array $_activeAttributes = null;
    private ?array $_safeAttributes = null;
    private ?array $_scenarios = null;

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

    public function createValidators(): ArrayObject
    {
        $event = new CreateValidatorsEvent();
        $event->validators = parent::createValidators();

        $this->trigger($event::EVENT_CREATE_VALIDATORS, $event);

        return $event->validators;
    }

    public function getTraitAttributeLabels(): array
    {
        $attributeLabels = [];

        foreach ($this->getTraitNames() as $traitName) {
            $method = "get{$traitName}AttributeLabels";

            if (method_exists($this, $method)) {
                $attributeLabels = [...$attributeLabels, ...$this->{$method}()];
            }
        }

        return $attributeLabels;
    }

    public function getTraitRules(): array
    {
        $rules = [];

        foreach ($this->getTraitNames() as $traitName) {
            $method = "get{$traitName}Rules";

            if (method_exists($this, $method)) {
                $rules = [
                    ...$rules,
                    ...$this->{$method}(),
                ];
            }
        }

        return $rules;
    }

    public function getTraitNames(): array
    {
        $traitNames = (new ReflectionClass($this))->getTraitNames();
        return array_map(fn ($name) => substr($name, strrpos($name, '\\') + 1), $traitNames);
    }

    public static function create(): static
    {
        return Yii::createObject(static::class);
    }
}
