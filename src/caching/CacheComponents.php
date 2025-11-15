<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\caching;

use Yii;
use yii\caching\Cache;

final class CacheComponents
{
    /**
     * @var Cache[]
     */
    private static array $components;

    public static function getAll(): array
    {
        return self::$components ??= self::findCaches();
    }


    private static function findCaches(): array
    {
        $caches = [];

        foreach (Yii::$app->getComponents() as $name => $component) {
            if ($component instanceof Cache) {
                $caches[$name] = $component::class;
            } elseif (is_array($component) && isset($component['class']) && self::isCacheClass($component['class'])) {
                $caches[$name] = $component['class'];
            } elseif (is_string($component) && self::isCacheClass($component)) {
                $caches[$name] = $component;
            }
        }

        ksort($caches);
        return $caches;
    }

    private static function isCacheClass(string $className): bool
    {
        return is_subclass_of($className, Cache::class);
    }
}
