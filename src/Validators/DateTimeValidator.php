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
 * A value still a string after {@see AttributeTypecastBehavior}'s cast is one it could not read, so a model without
 * the behavior never passes.
 */
class DateTimeValidator extends Validator implements AttributeTypeInterface
{
    /**
     * Only for an attribute without a column, which names its own.
     *
     * @var AttributeTypecastBehavior::TYPE_DATETIME|AttributeTypecastBehavior::TYPE_DATE
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
