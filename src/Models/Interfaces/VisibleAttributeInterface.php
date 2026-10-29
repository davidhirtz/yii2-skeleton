<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

interface VisibleAttributeInterface
{
    public function getVisibleAttribute(string $attribute, ?string $language = null): mixed;

    public function isAttributeVisible(string $attribute): bool;
}
