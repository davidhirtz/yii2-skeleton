<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Override;
use stdClass;

class ListResourcesTool extends ResourceTool
{
    #[Override]
    public function getName(): string
    {
        return 'list_resources';
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Lists the content resources this site offers. Call describe_resource for the attributes of one.';
    }

    #[Override]
    public function isAvailable(): bool
    {
        return true;
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new stdClass(),
            'additionalProperties' => false,
        ];
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    #[Override]
    protected function run(array $arguments): array
    {
        $resources = [];

        foreach ($this->mcp->getResources() as $resource) {
            $record = $resource->instantiate();

            if ($this->can($record)) {
                $resources[] = array_filter([
                    'name' => $resource->getName(),
                    'label' => $record->getAdminType(),
                    'has_types' => $resource->hasTypes(),
                    'parent' => $resource->getParentParam(),
                    'writable' => !$resource->isReadOnly(),
                    'creatable' => $resource->isCreatable(),
                    'reorderable' => $resource->isReorderable(),
                ], fn (mixed $value): bool => $value !== null);
            }
        }

        return ['resources' => $resources];
    }
}
