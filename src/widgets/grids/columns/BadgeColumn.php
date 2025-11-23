<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;

class BadgeColumn extends LinkColumn
{
    public ?array $headerAttributes = ['class' => 'text-center'];
    public array|null|Closure $contentAttributes = ['class' => 'text-center'];
    public array $linkAttributes = ['class' => 'badge'];

    public string $format = 'integer';
}
