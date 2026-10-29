<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Closure;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Types\Type;
use Yii;
use yii\base\InvalidConfigException;

/**
 * @property int $type
 */
trait TypeAttributeTrait
{
    /**
     * @var Closure(): list<Type>|list<Type>|null
     */
    private Closure|array|null $configuredTypes = null;

    /**
     * A caller holding a type rather than a row takes {@see static::instantiateByType()}, since a misspelled key here
     * falls through to the base class silently.
     *
     * @param array<string, mixed> $row
     */
    public static function instantiate($row): static
    {
        /** @var class-string<static> $className */
        $className = static::findType(static::normalizeTypeValue($row['type'] ?? null))?->getModelClass() ?? static::class;

        $model = $className::create();
        $model->setAttributes($row, false);

        return $model;
    }

    /**
     * A typed record is built here, never by assigning `type` to a built one, which keeps the base class's
     * definitions, rules and hooks unnoticed; {@see static::loadDefaultValues()} runs after this, or the column
     * default wins.
     */
    public static function instantiateByType(?int $type): static
    {
        return static::instantiate(['type' => $type]);
    }

    /**
     * The posted type decides the class before the record is built, since {@see static::load()} cannot change it;
     * `$type` is the route's fallback for the first request, which posts nothing.
     *
     * @param array<string, mixed> $data
     */
    public static function instantiateFromPost(array $data, ?int $type = null): static
    {
        $formName = static::instance()->formName();
        $attributes = ($formName === '' ? $data : $data[$formName] ?? null);

        $posted = is_array($attributes)
            ? static::normalizeTypeValue($attributes['type'] ?? null)
            : null;

        $model = static::instantiateByType($posted ?? $type);

        // A form name follows the runtime class, so a family answering different ones would post under one and
        // load under another, dropping everything typed on the type switch. The fix is an override pinning it.
        if ($model->formName() !== $formName) {
            throw new InvalidConfigException(
                $model::class . ', which a type of ' . static::class . ' names, answers the form name "'
                . $model->formName() . '" instead of "' . $formName . '" — a record built from what a form posted '
                . 'needs them to agree.'
            );
        }

        return $model;
    }

    /**
     * A closure, since a type's name is a {@see Yii::t()} result a configuration literal would freeze before the
     * application has an `i18n` component.
     *
     * @param Closure(): list<Type>|list<Type> $types
     */
    public function setTypes(Closure|array $types): void
    {
        $this->configuredTypes = $types;
    }

    /**
     * An override owns the types, and the configured list is then ignored.
     *
     * @return list<Type>
     */
    public function getTypes(): array
    {
        if ($this->configuredTypes !== null) {
            return $this->configuredTypes instanceof Closure
                ? ($this->configuredTypes)()
                : $this->configuredTypes;
        }

        return [$this->getDefaultType()];
    }

    /**
     * Unlike an override of {@see static::getTypes()}, an override of this still gives way to what the container
     * configured.
     */
    protected function getDefaultType(): Type
    {
        $class = static::getTypeClass();

        return $class::make(static::TYPE_DEFAULT)
            ->name(Yii::t('skeleton', 'TYPE_DEFAULT_NAME'));
    }

    /**
     * @return class-string<Type>
     */
    public static function getTypeClass(): string
    {
        return Type::class;
    }

    /**
     * @return array<int, Type>
     */
    public static function getTypeDefinitions(): array
    {
        return DefinitionRegistry::get(static::class, 'getTypes', static::getTypeClass());
    }

    public static function findType(?int $type): ?Type
    {
        return $type === null ? null : (static::getTypeDefinitions()[$type] ?? null);
    }

    /**
     * Nullable on purpose: a database holds rows whose type the code has since removed.
     */
    public function getType(): ?Type
    {
        return static::findType(static::normalizeTypeValue($this->type ?? null));
    }

    /**
     * Normalized here rather than in {@see static::findType()}, since a form posts a string and PDO answers one; a
     * narrowing `getType()` override has to call this too.
     */
    protected static function normalizeTypeValue(mixed $type): ?int
    {
        return is_numeric($type) ? (int)$type : null;
    }

    /**
     * @return array<int, static>
     */
    public static function getTypeInstances(): array
    {
        /** @var array<int, static> */
        return DefinitionRegistry::getInstances(static::class, static function (): array {
            $instances = [];

            foreach (static::getTypeDefinitions() as $value => $definition) {
                $instance = static::instantiateByType($value);
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
