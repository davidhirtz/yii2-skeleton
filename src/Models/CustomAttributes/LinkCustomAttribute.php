<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

class LinkCustomAttribute extends UrlCustomAttribute
{
    protected bool $relative = true;

    public function getJsonSchemaComment(): string
    {
        return 'An absolute URL (https:, mailto:, tel:) or a partial one starting with /, ? or #.';
    }
}
