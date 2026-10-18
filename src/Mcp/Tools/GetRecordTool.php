<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Override;

class GetRecordTool extends ResourceTool
{
    #[Override]
    public function getName(): string
    {
        return 'get_record';
    }

    #[Override]
    public function getTitle(): string
    {
        return 'Get a record';
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Returns every attribute of a record, the translations and custom attributes included, with its admin '
            . 'and frontend URLs.';
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'resource' => $this->getResourceProperty(),
                'id' => ['type' => ['integer', 'string']],
            ],
            'required' => ['resource', 'id'],
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
        $resource = $this->findResource($arguments);
        return $this->getRecordData($resource, $this->findRecord($resource, $arguments['id']));
    }
}
