<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;

trait VisibleAttributeTrait
{
    /**
     * `null` for an attribute the model does not declare, since a hidden field and a missing one mean the same to a
     * renderer.
     */
    public function getVisibleAttribute(string $attribute, ?string $language = null): mixed
    {
        if (!in_array($attribute, $this->attributes(), true) || !$this->isAttributeVisible($attribute)) {
            return null;
        }

        $value = $this->getI18nAttribute($attribute, $language);
        $definition = $this instanceof CustomAttributeInterface ? $this->getCustomAttribute($attribute) : null;

        return $definition ? $definition->formatVisibleValue($this, $value, $language) : $value;
    }

    public function isAttributeVisible(string $attribute): bool
    {
        $hiddenFields = $this instanceof TypeAttributeInterface
            ? $this->getType()?->getHiddenFields() ?? []
            : [];

        return !in_array($attribute, $hiddenFields, true);
    }
}
