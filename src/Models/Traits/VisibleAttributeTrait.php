<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;

trait VisibleAttributeTrait
{
    /**
     * `false` for an attribute the model does not declare at all, which a custom attribute is whenever the type or
     * the subclass leaves it out — the marker of a hidden field and a missing one mean the same to a renderer.
     */
    public function getVisibleAttribute(string $attribute): mixed
    {
        return in_array($attribute, $this->attributes(), true) && $this->isAttributeVisible($attribute)
            ? $this->getI18nAttribute($attribute)
            : false;
    }

    public function isAttributeVisible(string $attribute): bool
    {
        $hiddenFields = $this instanceof TypeAttributeInterface
            ? $this->getType()?->getHiddenFields() ?? []
            : [];

        return !in_array($attribute, $hiddenFields, true);
    }
}
