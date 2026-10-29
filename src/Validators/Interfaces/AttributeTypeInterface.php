<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators\Interfaces;

use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;

/**
 * For a validator {@see AttributeTypecastBehavior::detectAttributeTypes()} cannot recognise by class, whose attribute
 * would otherwise keep the string a form posted.
 */
interface AttributeTypeInterface
{
    /**
     * @return AttributeTypecastBehavior::TYPE_*|null
     */
    public function getAttributeType(): ?string;
}
