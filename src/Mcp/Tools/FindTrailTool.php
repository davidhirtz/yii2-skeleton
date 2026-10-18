<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use DateTimeZone;
use Exception;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;

/**
 * Only the trail of the resources the user may read: the admin's trail page also lists users and permissions, which
 * an agent has no business with.
 */
class FindTrailTool extends ResourceTool
{
    public int $maxLimit = 100;

    #[Override]
    public function getName(): string
    {
        return 'find_trail';
    }

    #[Override]
    public function getTitle(): string
    {
        return 'Find changes';
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Lists the recorded changes, newest first: of one record ("resource" and "id"), of a resource, by a '
            . 'user, through an MCP client, or since a date. An update lists each changed attribute as [old, new].';
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'resource' => $this->getResourceProperty(),
                'id' => ['type' => ['integer', 'string'], 'description' => 'A record of "resource".'],
                'user_id' => ['type' => 'integer'],
                'client' => ['type' => 'string', 'description' => 'The MCP client, e.g. "claude.ai" or "MCP token #12".'],
                'since' => ['type' => 'string', 'format' => 'date-time'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => $this->maxLimit, 'default' => 20],
                'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
            ],
            'additionalProperties' => false,
        ];
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    #[Override]
    public function isAvailable(): bool
    {
        return parent::isAvailable() && (WebUser::current()?->can(Trail::AUTH_TRAIL_INDEX) ?? false);
    }

    #[Override]
    protected function run(array $arguments): array
    {
        if (!(WebUser::current()?->can(Trail::AUTH_TRAIL_INDEX) ?? false)) {
            throw new ToolException('The trail is not available to this user.');
        }

        $query = Trail::find()->orderBy(['id' => SORT_DESC]);

        if (isset($arguments['resource'])) {
            $resource = $this->findResource($arguments);
            $query->andWhere(['model_class' => $this->getTrailModelClass($resource)]);

            if (isset($arguments['id'])) {
                $record = $this->findRecord($resource, $arguments['id']);
                $query->andWhere(['model_id' => implode('-', (array)$record->getPrimaryKey())]);
            }
        } elseif (isset($arguments['id'])) {
            throw new ToolException('An "id" needs its "resource".');
        } else {
            $query->andWhere(['model_class' => $this->getReadableModelClasses()]);
        }

        if (isset($arguments['user_id'])) {
            $query->andWhere(['user_id' => $arguments['user_id']]);
        }

        if (isset($arguments['client'])) {
            $query->andWhere(['client' => $arguments['client']]);
        }

        if (is_string($arguments['since'] ?? null)) {
            try {
                $since = new DateTime($arguments['since']);
            } catch (Exception) {
                throw new ToolException("Invalid since \"{$arguments['since']}\".");
            }

            $query->andWhere(['>=', 'created_at', $since->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')]);
        }

        $limit = min($this->maxLimit, max(1, (int)($arguments['limit'] ?? 20)));
        $offset = max(0, (int)($arguments['offset'] ?? 0));

        $total = (int)(clone $query)->orderBy(null)->count();
        $records = [];

        foreach ($query->with('user')->limit($limit)->offset($offset)->all() as $trail) {
            $records[] = $this->getTrailData($trail);
        }

        return [
            'total' => $total,
            'offset' => $offset,
            'records' => $records,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTrailData(Trail $trail): array
    {
        return array_filter([
            'id' => $trail->id,
            'type' => $trail->getTypeName(),
            'resource' => $this->findResourceName((string)$trail->model_class),
            'record_id' => $trail->model_id,
            'record_name' => $trail->model_class ? $trail->getModelName() : null,
            'message' => $trail->getMessage(),
            'changes' => $trail->hasAttributesEnabled() ? $this->formatValue($trail->data) : null,
            'user' => $trail->user ? ['id' => $trail->user->id, 'name' => $trail->user->getUsername()] : null,
            'client' => $trail->client,
            'created_at' => $this->formatValue($trail->created_at),
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return list<string>
     */
    protected function getReadableModelClasses(): array
    {
        $classes = [];

        foreach ($this->getResources() as $resource) {
            if ($this->can($resource->instantiate())) {
                $classes[] = $this->getTrailModelClass($resource);
            }
        }

        return $classes ?: [''];
    }

    /**
     * @param Resource<covariant ActiveRecord&AdminModelInterface> $resource
     */
    protected function getTrailModelClass(Resource $resource): string
    {
        $record = $resource->instantiate();

        return ($record instanceof TrailModelInterface ? $record->getTrailBehavior()->modelClass : null)
            ?? $resource->getModelClass();
    }

    protected function findResourceName(string $modelClass): ?string
    {
        foreach ($this->getResources() as $resource) {
            if ($this->getTrailModelClass($resource) === $modelClass) {
                return $resource->getName();
            }
        }

        return null;
    }
}
