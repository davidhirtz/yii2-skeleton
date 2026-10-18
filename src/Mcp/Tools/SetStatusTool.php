<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Override;

class SetStatusTool extends ResourceTool
{
    #[Override]
    public function getName(): string
    {
        return 'set_status';
    }

    #[Override]
    public function getTitle(): string
    {
        return 'Set the status';
    }

    #[Override]
    public function isDestructive(): bool
    {
        return true;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Sets the status of a record, one of the ids describe_resource lists, e.g. to publish or unpublish it.';
    }

    #[Override]
    public function getResources(): array
    {
        return array_values(array_filter(
            parent::getResources(),
            fn (Resource $resource): bool => !$resource->isReadOnly()
                && is_subclass_of($resource->getModelClass(), StatusAttributeInterface::class),
        ));
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'resource' => $this->getResourceProperty(),
                'id' => ['type' => ['integer', 'string']],
                'status' => ['type' => 'integer'],
            ],
            'required' => ['resource', 'id', 'status'],
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
    protected function run(array $arguments): array
    {
        $resource = $this->findResource($arguments, true);
        $record = $this->findRecord($resource, $arguments['id']);

        // What the grid's status icon refuses, this refuses too: a record the model exempts, an undeclared status
        if (!$record instanceof StatusAttributeInterface || !$record->isStatusUpdatable()) {
            throw new ToolException("The status of this {$resource->getName()} cannot be changed.");
        }

        if (!$record::findStatus(is_int($arguments['status']) ? $arguments['status'] : null)) {
            throw new ToolException("Unknown status {$arguments['status']}, see describe_resource.");
        }

        $record->setAttribute('status', $arguments['status']);

        if ($record->update() === false) {
            $this->throwValidationErrors($record);
        }

        return $this->getRecordSummary($record);
    }
}
