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
            . 'and frontend URLs; a file that is an image comes with a preview of it.';
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

    /**
     * Overridden rather than {@see static::run()}, as the record's picture travels beside the JSON.
     */
    #[Override]
    public function call(array $arguments): ToolResult
    {
        try {
            $resource = $this->findResource($arguments);
            $record = $this->findRecord($resource, $arguments['id']);
            $image = $resource->getImage($record);

            return ToolResult::success(
                $this->getRecordData($resource, $record),
                $image ? [['type' => 'image', ...$image]] : [],
            );
        } catch (ToolException $exception) {
            return ToolResult::error($exception->getMessage(), $exception->data);
        }
    }

    #[Override]
    protected function run(array $arguments): array
    {
        $resource = $this->findResource($arguments);
        return $this->getRecordData($resource, $this->findRecord($resource, $arguments['id']));
    }
}
