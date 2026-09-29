<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base\Traits;

use ReflectionMethod;
use ReflectionProperty;
use Yii;
use yii\base\Configurable;
use yii\base\InvalidConfigException;

/**
 * Yii's container assigns a definition's properties from outside unless the class is `Configurable`, which a class
 * with protected options cannot survive: such a class implements it and passes its constructor's config to
 * `configureProperties()`.
 */
trait ContainerConfigurationTrait
{
    /**
     * The container hands a `Configurable` class its definition in place of the last constructor argument, so a
     * config passed here goes in as the definition's, overriding it key by key.
     *
     * @param mixed ...$args
     */
    public static function make(...$args): static
    {
        if (count($args) === 1 && is_array($args[0]) && is_a(static::class, Configurable::class, true)) {
            return Yii::$container->get(static::class, [], $args[0]);
        }

        return Yii::createObject(static::class, $args);
    }

    /**
     * Assigns a public property, and calls the public method of the same name for anything else, spreading an
     * array into a variadic one.
     *
     * @param array<string, mixed> $config
     */
    protected function configureProperties(array $config): void
    {
        foreach ($config as $name => $value) {
            if (property_exists($this, $name) && (new ReflectionProperty($this, $name))->isPublic()) {
                $this->$name = $value;
                continue;
            }

            if (!method_exists($this, $name) || !($method = new ReflectionMethod($this, $name))->isPublic()) {
                throw new InvalidConfigException(static::class . " has neither a public property nor a public method \"$name\".");
            }

            if ($method->isVariadic() && $method->getNumberOfParameters() === 1 && is_array($value)) {
                $this->$name(...$value);
                continue;
            }

            $this->$name($value);
        }
    }
}
