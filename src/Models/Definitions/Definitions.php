<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Definitions;

use Closure;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Types\Type;
use Override;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * The `definitions` application component, which lets an installation declare a model's types in its configuration
 * instead of subclassing the model — often all a small project needs to be fully configured.
 */
class Definitions extends Component
{
    /**
     * The key is the class the container resolves the model to, so a project that re-points `Entry::class` to one of
     * its own names that class here rather than the bundle's. The value is a closure because a type's name is a
     * {@see Yii::t()} result: an array literal in a configuration file would resolve it before the application has
     * an `i18n` component, and freeze every definition to whichever language happened to be current.
     *
     * @var array<string, Closure(): list<Type>>
     */
    public array $types = [];

    #[Override]
    public function init(): void
    {
        foreach (array_keys($this->types) as $modelClass) {
            if (!is_a((string)$modelClass, TypeAttributeInterface::class, true)) {
                throw new InvalidConfigException(static::class . " configures types for \"$modelClass\", which does not implement " . TypeAttributeInterface::class . '.');
            }
        }

        parent::init();
    }

    /**
     * @param class-string $modelClass
     * @return list<Type>|null
     */
    public function findTypes(string $modelClass): ?array
    {
        return isset($this->types[$modelClass]) ? ($this->types[$modelClass])() : null;
    }
}
