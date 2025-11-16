<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

class DashboardPanel
{
    /**
     * @param array<string, DashboardItem> $items
     */
    public function __construct(
        public string $title,
        public array $items = [],
        public array $roles = [],
        public array $attributes = [],
    )
    {
    }
}