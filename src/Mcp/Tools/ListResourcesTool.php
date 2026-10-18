<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Hirtz\Skeleton\Mcp\Mcp;
use Override;
use stdClass;

class ListResourcesTool implements ToolInterface
{
    public function __construct(protected Mcp $mcp)
    {
    }

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
    public function isIdempotent(): bool
    {
        return true;
    }

    #[Override]
    public function call(array $arguments): ToolResult
    {
        $resources = [];

        foreach ($this->mcp->getResources() as $resource) {
            $resources[] = [
                'name' => $resource->getName(),
                'label' => $resource->instantiate()->getAdminType(),
            ];
        }

        return ToolResult::success(['resources' => $resources]);
    }
}
