<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base\Traits;

use ArrayObject;
use Hirtz\Skeleton\Models\Events\CreateValidatorsEvent;
use Yii;

trait ModelTrait
{
    private ?array $scenarios = null;
    private ?ArrayObject $validators = null;

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

    public function getValidators(): ArrayObject
    {
        return $this->validators ??= $this->createValidators();
    }

    /**
     * Must be called whenever the rules of a model change after its validators were first built, e.g. when an attribute
     * the rules depend on was assigned.
     */
    public function resetValidators(): void
    {
        $this->validators = null;
        $this->scenarios = null;
    }

    public function createValidators(): ArrayObject
    {
        $event = new CreateValidatorsEvent();
        $event->validators = parent::createValidators();

        $this->trigger($event::EVENT_CREATE_VALIDATORS, $event);

        return $event->validators;
    }

    public static function create(array $params = []): static
    {
        return Yii::createObject(static::class, $params);
    }
}
