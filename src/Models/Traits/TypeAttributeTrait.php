<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Closure;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Types\Type;
use Yii;
use yii\base\InvalidConfigException;

/**
 * {@see static::getTypes()} is the declaration; everything reads {@see static::getTypeDefinitions()},
 * {@see static::findType()} or {@see static::getType()}, which are resolved, validated and cached.
 *
 * @property int $type
 */
trait TypeAttributeTrait
{
    /**
     * @var Closure(): list<Type>|list<Type>|null what the container configured, see {@see static::setTypes()}
     */
    private Closure|array|null $configuredTypes = null;

    /**
     * Instantiates a class based on the given `type`. In contrast to the original implementation, this can be used for
     * creating new records directly, as it also populates the model.
     *
     * A caller holding a type rather than a row takes {@see static::instantiateByType()}, whose `?int` the analyser
     * checks — a misspelled key here falls through to the base class as silently as no key at all.
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
     * **A record whose type is known is built here, never through `create()` or {@see Yii::createObject()}.**
     * Assigning `type` to an already-built record leaves the base class, with the base class's definitions, rules
     * and lifecycle hooks — and nothing catches it, since the wrong class is a valid instance of the right base.
     * {@see static::loadDefaultValues()} runs after this, or the column default wins over the type asked for.
     */
    public static function instantiateByType(?int $type): static
    {
        return static::instantiate(['type' => $type]);
    }

    /**
     * The type a form posted decides the class, so it is read before the record is built: a type select reloads the
     * form by posting to the same action, and {@see static::load()} cannot change the class of a record that already
     * exists. `$type` is the fallback the route carries, for the first request, which posts nothing.
     *
     * @param array<string, mixed> $data the request body, as {@see static::load()} takes it
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
     * A closure, because a type's name is a {@see Yii::t()} result and a literal in a configuration file resolves
     * before the application has an `i18n` component — which would freeze every definition to one language. A plain
     * list is accepted for a declaration that needs no translation.
     *
     * @param Closure(): list<Type>|list<Type> $types
     */
    public function setTypes(Closure|array $types): void
    {
        $this->configuredTypes = $types;
    }

    /**
     * Override this method to implement types — an override owns them, and the configured list is then ignored.
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

        $class = static::getTypeClass();

        return [
            $class::make(static::TYPE_DEFAULT)
                ->name(Yii::t('skeleton', 'COMMON_TYPE_DEFAULT')),
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
     * The attribute is whatever was assigned or read — a form posts a string and PDO answers one for an integer
     * column — so it is normalized here rather than in {@see static::findType()}, which is the typed API. A
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
