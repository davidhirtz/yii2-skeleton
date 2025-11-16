<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

class DashboardItem
{
    public function __construct(
        public string $label,
        public array|string $url,
        public ?string $icon = null,
        public array $roles = [],
        public array $attributes = [],
    )
    {
    }
}