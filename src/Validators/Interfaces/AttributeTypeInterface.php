<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators\Interfaces;

use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;

/**
 * Declares the type a validator's attributes hold, for a validator
 * {@see AttributeTypecastBehavior::detectAttributeTypes()} cannot recognise by class. Without it the attribute keeps
 * the string a form posted, which a form reload never validates away.
 */
interface AttributeTypeInterface
{
    /**
     * @return AttributeTypecastBehavior::TYPE_*|null
     */
    public function getAttributeType(): ?string;
}
