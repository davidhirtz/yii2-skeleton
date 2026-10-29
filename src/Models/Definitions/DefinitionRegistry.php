<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Definitions;

use Closure;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Cached per application language, since a definition's `name` is a {@see Yii::t()} result; models are built with
 * {@see Yii::createObject()}, since `instance()` caches process-wide past the application.
 */
final class DefinitionRegistry
{
    /**
     * @var array<string, array<int, ModelDefinition>>
     */
    private static array $definitions = [];

    /**
     * @var array<string, array<int, object>>
     */
    private static array $instances = [];

    /**
     * @template T of ModelDefinition
     * @param class-string $modelClass
     * @param class-string<T> $definitionClass
     * @return array<int, T>
     */
    public static function get(string $modelClass, string $method, string $definitionClass): array
    {
        $key = self::getCacheKey($modelClass, $method);

        if (isset(self::$definitions[$key])) {
            /** @var array<int, T> */
            return self::$definitions[$key];
        }

        $definitions = [];

        /** @var iterable<mixed> $declared */
        $declared = Yii::createObject($modelClass)->$method();

        foreach ($declared as $definition) {
            if (!$definition instanceof $definitionClass) {
                $given = get_debug_type($definition);
                throw new InvalidConfigException("$modelClass::$method() must return a list of $definitionClass, got $given.");
            }

            if (isset($definitions[$definition->value])) {
                throw new InvalidConfigException("$modelClass::$method() declares \"$definition->value\" twice.");
            }

            $definitions[$definition->value] = $definition;
        }

        // Cached before validation, so a definition that validates against the same model's set — a trail's parent
        // type — does not re-enter this method and recurse.
        self::$definitions[$key] = $definitions;

        try {
            foreach ($definitions as $definition) {
                $definition->validate($modelClass);
            }
        } catch (Throwable $exception) {
            unset(self::$definitions[$key]);
            throw $exception;
        }

        /** @var array<int, T> */
        return $definitions;
    }

    /**
     * Cached here rather than in the trait, whose `private static` would be separate per using class.
     *
     * @param class-string $modelClass
     * @param Closure(): array<int, object> $create
     * @return array<int, object>
     */
    public static function getInstances(string $modelClass, Closure $create): array
    {
        $key = self::getCacheKey($modelClass, 'instances');
        return self::$instances[$key] ??= $create();
    }

    public static function reset(): void
    {
        self::$definitions = [];
        self::$instances = [];
    }

    /**
     * @param class-string $modelClass
     */
    public static function resetClass(string $modelClass): void
    {
        foreach ([...array_keys(self::$definitions), ...array_keys(self::$instances)] as $key) {
            if (str_starts_with($key, "$modelClass::")) {
                unset(self::$definitions[$key], self::$instances[$key]);
            }
        }
    }

    private static function getCacheKey(string $modelClass, string $method): string
    {
        return "$modelClass::$method::" . Yii::$app->language;
    }
}
