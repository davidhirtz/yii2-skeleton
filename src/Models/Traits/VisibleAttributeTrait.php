<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;

trait VisibleAttributeTrait
{
    public function getVisibleAttribute(string $attribute): mixed
    {
        return $this->isAttributeVisible($attribute) ? $this->getI18nAttribute($attribute) : false;
    }

    public function isAttributeVisible(string $attribute): bool
    {
        $hiddenFields = $this instanceof TypeAttributeInterface
            ? $this->getType()?->getHiddenFields() ?? []
            : [];

        return !in_array($attribute, $hiddenFields, true);
    }
}
