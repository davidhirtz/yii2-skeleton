<?php
declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\config;

final readonly class Config
{
    /**
     * @param array<string, ConfigInterface|null> $items
     */
    public static function merge(array &$items, string $key, ConfigInterface|null $item): void
    {
        $items[$key] = null !== $item && !empty($items[$key])
            ? $items[$key]->merge($item)
            : $item;
    }
}
