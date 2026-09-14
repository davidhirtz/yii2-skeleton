<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Types\Type;
use Yii;

/**
 * {@see static::getTypes()} is the declaration; everything reads {@see static::getTypeDefinitions()},
 * {@see static::findType()} or {@see static::getType()}, which are resolved, validated and cached.
 *
 * @property int|string $type
 */
trait TypeAttributeTrait
{
    /**
     * Instantiates a class based on the given `type`. In contrast to the original implementation, this can be used for
     * creating new records directly, as it also populates the model.
     */
    public static function instantiate($row): static
    {
        /** @var class-string<static> $className */
        $className = static::findType($row['type'] ?? null)?->getModelClass() ?? static::class;

        $model = $className::create();
        $model->setAttributes($row, false);

        return $model;
    }

    /**
     * Override this method to implement types.
     *
     * @return list<Type>
     */
    public static function getTypes(): array
    {
        return [
            Type::make(static::TYPE_DEFAULT)
                ->name(Yii::t('skeleton', 'BASED_DEFAULT')),
        ];
    }

    /**
     * @return class-string<Type>
     */
    public static function getTypeClass(): string
    {
        return Type::class;
    }

    /**
     * @return array<int|string, Type>
     */
    public static function getTypeDefinitions(): array
    {
        return DefinitionRegistry::get(static::class, 'getTypes', static::getTypeClass());
    }

    public static function findType(int|string|null $type): ?Type
    {
        return $type === null || $type === '' ? null : (static::getTypeDefinitions()[$type] ?? null);
    }

    /**
     * Nullable on purpose: a database holds rows whose type the code has since removed.
     */
    public function getType(): ?Type
    {
        return static::findType($this->type ?? null);
    }

    /**
     * @return array<int|string, static>
     */
    public static function getTypeInstances(): array
    {
        /** @var array<int|string, static> */
        return DefinitionRegistry::getInstances(static::class, static function (): array {
            $instances = [];

            foreach (static::getTypeDefinitions() as $value => $definition) {
                $instance = static::instantiate(['type' => $value]);
                $instance->type = $value;

                $instances[$value] = $instance;
            }

            return $instances;
        });
    }

    public function getTypeName(): string
    {
        return $this->getType()?->getName() ?? '';
    }

    public function getTypePlural(): string
    {
        return $this->getType()?->getPlural() ?? '';
    }

    public function getTypeIcon(): string
    {
        return $this->getType()?->getIcon() ?? '';
    }
}
