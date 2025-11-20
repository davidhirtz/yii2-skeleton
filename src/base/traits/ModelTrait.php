<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\base\traits;

use ArrayObject;
use davidhirtz\yii2\skeleton\models\events\CreateValidatorsEvent;
use ReflectionClass;
use Yii;

trait ModelTrait
{
    private ?array $scenarios = null;

    public function addInvalidAttributeError(string $attribute): bool
    {
        $this->addError($attribute, Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $this->getAttributeLabel($attribute),
        ]));

        return false;
    }

    public function scenarios(): array
    {
        return $this->scenarios ??= parent::scenarios();
    }

    public function setScenario($value): void
    {
        $this->scenarios = null;
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

    public static function create(array $params = []): static
    {
        return Yii::createObject(static::class, $params);
    }
}
