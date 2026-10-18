<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Closure;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;

/**
 * What the tools need to know about a model beyond what the model itself says. Each closure reproduces what the
 * model's admin action does besides `load()` and `insert()`.
 *
 * @template T of ActiveRecord&AdminModelInterface
 */
class Resource
{
    protected ?string $parentParam = null;

    /**
     * @var class-string<ActiveRecord&AdminModelInterface>|null
     */
    protected ?string $parentClass = null;

    protected ?Closure $populate = null;

    protected bool $parentRequired = true;

    protected ?Closure $query = null;

    protected ?Closure $attributes = null;

    /**
     * @var list<string>
     */
    protected array $exceptAttributes = [];

    protected ?Closure $beforeInsert = null;

    protected ?Closure $reorder = null;

    protected ?Closure $url = null;

    protected bool $readOnly = false;

    protected bool $creatable = true;

    protected ?Closure $defaultType = null;

    /**
     * @param class-string<T> $modelClass
     */
    final public function __construct(public readonly string $name, public readonly string $modelClass)
    {
    }

    /**
     * @template TModel of ActiveRecord&AdminModelInterface
     * @param class-string<TModel> $modelClass
     * @return static<TModel>
     */
    public static function make(string $name, string $modelClass): static
    {
        return new static($name, $modelClass);
    }

    /**
     * @template TParent of ActiveRecord&AdminModelInterface
     * @param class-string<TParent> $parentClass
     * @param Closure(T, TParent|null): mixed $populate called before `load()`, the parent `null` only when it is
     * optional; `false` refuses the parent as if it did not exist
     */
    public function parent(string $param, string $parentClass, Closure $populate, bool $required = true): static
    {
        $this->parentParam = $param;
        $this->parentClass = $parentClass;
        $this->populate = $populate;
        $this->parentRequired = $required;

        return $this;
    }

    /**
     * @param Closure(ActiveQuery<T>): (ActiveQuery<T>|null) $query
     */
    public function query(Closure $query): static
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param Closure(T, list<string>): list<string> $attributes receives the record and the default
     * list, see {@see SchemaBuilder::getWritableAttributes()}
     */
    public function attributes(Closure $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function exceptAttributes(string ...$attributes): static
    {
        $this->exceptAttributes = array_values($attributes);
        return $this;
    }

    /**
     * @param Closure(T): void $beforeInsert called after `load()`
     */
    public function beforeInsert(Closure $beforeInsert): static
    {
        $this->beforeInsert = $beforeInsert;
        return $this;
    }

    /**
     * @param Closure(?ActiveRecord, list<int>): bool $reorder receives the parent, if the resource has one, and the
     * ids in their new order
     */
    public function reorder(Closure $reorder): static
    {
        $this->reorder = $reorder;
        return $this;
    }

    /**
     * @param Closure(T): (array<array-key, mixed>|string|false|null) $url the frontend route or URL
     */
    public function url(Closure $url): static
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param Closure(): ?int $defaultType the type a record is created as when the caller names none
     */
    public function defaultType(Closure $defaultType): static
    {
        $this->defaultType = $defaultType;
        return $this;
    }

    /**
     * A resource that is not creatable is updated only, its records created elsewhere (a file by `attach_file`).
     */
    public function creatable(bool $creatable = true): static
    {
        $this->creatable = $creatable;
        return $this;
    }

    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    public function getDefaultType(): ?int
    {
        return $this->defaultType !== null ? ($this->defaultType)() : null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return class-string<T>
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getParentParam(): ?string
    {
        return $this->parentParam;
    }

    /**
     * @return class-string<ActiveRecord&AdminModelInterface>|null
     */
    public function getParentClass(): ?string
    {
        return $this->parentClass;
    }

    public function isParentRequired(): bool
    {
        return $this->parentParam !== null && $this->parentRequired;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function isCreatable(): bool
    {
        return $this->creatable && !$this->readOnly;
    }

    public function isReorderable(): bool
    {
        return $this->reorder !== null && !$this->readOnly;
    }

    public function hasTypes(): bool
    {
        return is_subclass_of($this->modelClass, TypeAttributeInterface::class)
            && count($this->modelClass::getTypeDefinitions()) > 1;
    }

    /**
     * Through the type, so the class the type names is the one instantiated, as `instantiateByType()` does.
     */
    public function instantiate(?int $type = null): ActiveRecord&AdminModelInterface
    {
        $modelClass = $this->modelClass;

        $record = is_subclass_of($modelClass, TypeAttributeInterface::class)
            ? $modelClass::instantiate(['type' => $type])
            : $modelClass::create();

        /** @var ActiveRecord&AdminModelInterface $record */
        return $record;
    }

    /**
     * @return ActiveQuery<ActiveRecord>
     */
    public function find(): ActiveQuery
    {
        /** @var ActiveQuery<ActiveRecord> $query */
        $query = $this->modelClass::find();

        if ($this->query !== null) {
            $query = ($this->query)($query) ?? $query;
        }

        return $query;
    }

    public function populate(ActiveRecord $record, ?ActiveRecord $parent): bool
    {
        return $this->populate === null || ($this->populate)($record, $parent) !== false;
    }

    /**
     * @param list<string> $attributes
     * @return list<string>
     */
    public function filterAttributes(ActiveRecord $record, array $attributes): array
    {
        $attributes = array_values(array_diff($attributes, $this->exceptAttributes));
        return $this->attributes !== null ? ($this->attributes)($record, $attributes) : $attributes;
    }

    public function beforeInsertRecord(ActiveRecord $record): void
    {
        if ($this->beforeInsert !== null) {
            ($this->beforeInsert)($record);
        }
    }

    /**
     * @param list<int> $ids
     */
    public function reorderRecords(?ActiveRecord $parent, array $ids): bool
    {
        return $this->reorder !== null && ($this->reorder)($parent, $ids);
    }

    /**
     * @return array<array-key, mixed>|string|false|null
     */
    public function getUrl(ActiveRecord $record): array|string|false|null
    {
        return $this->url !== null ? ($this->url)($record) : null;
    }
}
