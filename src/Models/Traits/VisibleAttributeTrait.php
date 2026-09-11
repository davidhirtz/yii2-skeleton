<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

trait VisibleAttributeTrait
{
    public function getVisibleAttribute(string $attribute): mixed
    {
        return $this->isAttributeVisible($attribute) ? $this->getI18nAttribute($attribute) : false;
    }

    public function isAttributeVisible(string $attribute): bool
    {
        return !in_array($attribute, $this->getTypeOptions()['hiddenFields'] ?? [], true);
    }
}
