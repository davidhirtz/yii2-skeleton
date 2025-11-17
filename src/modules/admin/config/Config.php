<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\config;

final readonly class Config
{
    public static function merge(array $items, string $key, ConfigInterface|null $item): array
    {
        $items[$key] = null !== $item && !empty($items[$key])
            ? $items[$key]->merge($item)
            : $item;

        return $items;
    }
}
