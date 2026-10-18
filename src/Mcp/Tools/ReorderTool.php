<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Hirtz\Skeleton\Mcp\Resource;
use Override;

class ReorderTool extends ResourceTool
{
    #[Override]
    public function getName(): string
    {
        return 'reorder';
    }

    #[Override]
    public function getTitle(): string
    {
        return 'Reorder records';
    }

    #[Override]
    public function isDestructive(): bool
    {
        return true;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Puts the records of a reorderable resource in the given order, under the parent where the resource '
            . 'has one. Only the records given are renumbered, so pass all of them.';
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'resource' => $this->getResourceProperty(),
                'parent' => ['type' => 'integer', 'description' => 'The id of the parent record, see describe_resource.'],
                'ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'minItems' => 1],
            ],
            'required' => ['resource', 'ids'],
            'additionalProperties' => false,
        ];
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return false;
    }

    #[Override]
    public function isIdempotent(): bool
    {
        return true;
    }

    #[Override]
    public function getResources(): array
    {
        return array_values(array_filter(
            parent::getResources(),
            fn (Resource $resource): bool => $resource->isReorderable(),
        ));
    }

    #[Override]
    protected function run(array $arguments): array
    {
        $resource = $this->findResource($arguments, true);

        if (!$resource->isReorderable()) {
            throw new ToolException("Resource {$resource->getName()} cannot be reordered.");
        }

        $parent = $resource->getParentParam() !== null ? $this->findParent($resource, $arguments['parent'] ?? null) : null;

        /** @var list<mixed> $ids */
        $ids = $arguments['ids'];

        return [
            'reordered' => $resource->reorderRecords($parent, array_values(array_filter(array_map(intval(...), $ids)))),
        ];
    }
}
