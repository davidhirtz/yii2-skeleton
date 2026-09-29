<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators;

use DateTimeInterface;
use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Validators\Interfaces\AttributeTypeInterface;
use Override;
use Yii;
use yii\validators\Validator;

/**
 * Declares a date for {@see AttributeTypecastBehavior}, which reads the posted value; a value still a string after
 * the cast is one it could not read. A model without the behavior never passes.
 */
class DateTimeValidator extends Validator implements AttributeTypeInterface
{
    /**
     * @var AttributeTypecastBehavior::TYPE_DATETIME|AttributeTypecastBehavior::TYPE_DATE the type of an attribute
     * without a column, which names its own
     */
    public string $type = AttributeTypecastBehavior::TYPE_DATETIME;

    #[Override]
    public function init(): void
    {
        $this->message ??= Yii::t('yii', 'The format of {attribute} is invalid.');
        parent::init();
    }

    public function getAttributeType(): string
    {
        return $this->type;
    }

    /**
     * @return array{string, array<string, mixed>}|null
     */
    #[Override]
    protected function validateValue($value): ?array
    {
        return $value instanceof DateTimeInterface ? null : [$this->message, []];
    }
}
