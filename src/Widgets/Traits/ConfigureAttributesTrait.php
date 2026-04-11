<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Widgets\Attributes\Configure;
use ReflectionClass;
use ReflectionMethod;

trait ConfigureAttributesTrait
{
    /**
     * @var array<class-string, list<string>>
     */
    private static array $attributes = [];

    private function configureAttributes(): void
    {
        foreach (static::getResolvedAttributes() as $method) {
            $this->$method();
        }
    }

    /**
     * @return list<string>
     */
    private static function getResolvedAttributes(): array
    {
        return self::$attributes[static::class] ??= array_map(
            static fn (ReflectionMethod $method) => $method->getName(),
            array_filter(
                (new ReflectionClass(static::class))->getMethods(),
                static fn (ReflectionMethod $method) => $method->getAttributes(Configure::class) !== [],
            ),
        );
    }
}
