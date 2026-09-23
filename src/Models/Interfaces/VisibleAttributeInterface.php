<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Traits\VisibleAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;

/**
 * A model whose type can declare that it does not use an attribute, see {@see Type::hiddenFields()}. Implemented via
 * {@see VisibleAttributeTrait}.
 */
interface VisibleAttributeInterface
{
    public function getVisibleAttribute(string $attribute, ?string $language = null): mixed;

    public function isAttributeVisible(string $attribute): bool;
}
