<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use DateTimeInterface;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Web\Application as WebApplication;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Stringable;
use Throwable;
use Yii;

/**
 * A record the user may not reach is answered exactly like one that does not exist.
 */
abstract class ResourceTool implements ToolInterface
{
    public function __construct(protected Mcp $mcp)
    {
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    abstract protected function run(array $arguments): array;

    #[Override]
    public function call(array $arguments): ToolResult
    {
        try {
            return ToolResult::success($this->run($arguments));
        } catch (ToolException $exception) {
            return ToolResult::error($exception->getMessage(), $exception->data);
        }
    }

    #[Override]
    public function isIdempotent(): bool
    {
        return $this->isReadOnly();
    }

    /**
     * @param list<Resource<covariant ActiveRecord&AdminModelInterface>>|null $resources
     * @return array<string, mixed>
     */
    protected function getResourceProperty(?array $resources = null): array
    {
        $resources ??= array_values($this->mcp->getResources());

        return [
            'type' => 'string',
            'description' => 'The resource name, see list_resources.',
            'enum' => array_map(fn (Resource $resource): string => $resource->getName(), $resources),
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @return Resource<covariant ActiveRecord&AdminModelInterface>
     */
    protected function findResource(array $arguments, bool $writable = false): Resource
    {
        $name = $arguments['resource'] ?? null;
        $resource = is_string($name) ? $this->mcp->getResource($name) : null;

        if ($resource === null || !$this->can($resource->instantiate())) {
            throw new ToolException('Resource not found: ' . (is_string($name) ? $name : ''));
        }

        if ($writable && $resource->isReadOnly()) {
            throw new ToolException("Resource {$resource->getName()} is read-only.");
        }

        return $resource;
    }

    /**
     * @param Resource<covariant ActiveRecord&AdminModelInterface> $resource
     */
    protected function findRecord(Resource $resource, mixed $id): ActiveRecord&AdminModelInterface
    {
        $primaryKey = $resource->getModelClass()::primaryKey()[0] ?? 'id';
        $record = is_int($id) || is_string($id)
            ? $resource->find()->andWhere([$resource->getModelClass()::tableName() . ".[[$primaryKey]]" => $id])->one()
            : null;

        if (!$record instanceof AdminModelInterface || !$this->can($record)) {
            throw new ToolException("Record not found: {$resource->getName()} " . (is_scalar($id) ? $id : ''));
        }

        return $record;
    }

    protected function can(AdminModelInterface $record): bool
    {
        return WebUser::current()?->can($record->getPermissionName()) ?? false;
    }

    /**
     * @param Resource<covariant ActiveRecord&AdminModelInterface> $resource
     * @return array<string, mixed>
     */
    protected function getRecordSummary(Resource $resource, ActiveRecord&AdminModelInterface $record): array
    {
        return array_filter([
            'id' => $record->getPrimaryKey(),
            'name' => $record->getAdminName(),
            'type' => $record instanceof TypeAttributeInterface ? $record->getAttribute('type') : null,
            'status' => $record instanceof StatusAttributeInterface ? $record->getAttribute('status') : null,
            'updated_at' => $record->hasAttribute('updated_at') ? $this->formatValue($record->getAttribute('updated_at')) : null,
            'admin_url' => $this->getAbsoluteUrl($record->getAdminRoute()),
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param Resource<covariant ActiveRecord&AdminModelInterface> $resource
     * @return array<string, mixed>
     */
    protected function getRecordData(Resource $resource, ActiveRecord&AdminModelInterface $record): array
    {
        $attributes = $record->getAttributes();

        if ($record instanceof CustomAttributeInterface) {
            unset($attributes[$record->getCustomAttributesColumn()]);
        }

        return array_filter([
            'resource' => $resource->getName(),
            'id' => $record->getPrimaryKey(),
            'name' => $record->getAdminName(),
            'type_name' => $record instanceof TypeAttributeInterface ? $record->getTypeName() : null,
            'status_name' => $record instanceof StatusAttributeInterface ? $record->getStatusName() : null,
            'attributes' => array_map($this->formatValue(...), $attributes),
            'admin_url' => $this->getAbsoluteUrl($record->getAdminRoute()),
            'url' => $this->getAbsoluteUrl($resource->getUrl($record)),
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    protected function formatValue(mixed $value): mixed
    {
        if ($value instanceof Date) {
            return (string)$value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Stringable) {
            return (string)$value;
        }

        if (is_array($value)) {
            return array_map($this->formatValue(...), $value);
        }

        return is_scalar($value) ? $value : null;
    }

    /**
     * A console process knows no host unless one is configured, and a relative URL is of no use to the caller.
     *
     * @param array<array-key, mixed>|string|false|null $route
     */
    protected function getAbsoluteUrl(array|string|false|null $route): ?string
    {
        if (!$route) {
            return null;
        }

        $urlManager = Yii::$app->getUrlManager();

        if (!Yii::$app instanceof WebApplication && !$urlManager->isHostInfoConfigured()) {
            return null;
        }

        try {
            return Url::to($route, true);
        } catch (Throwable $exception) {
            Yii::warning($exception, __METHOD__);
            return null;
        }
    }
}
