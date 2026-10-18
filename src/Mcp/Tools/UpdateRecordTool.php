<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Hirtz\Skeleton\Models\Interfaces\StaleSaveInterface;
use Hirtz\Skeleton\Mcp\Resource;
use Override;

class UpdateRecordTool extends ResourceTool
{
    #[Override]
    public function getName(): string
    {
        return 'update_record';
    }

    #[Override]
    public function getTitle(): string
    {
        return 'Update a record';
    }

    #[Override]
    public function isDestructive(): bool
    {
        return true;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Updates the given attributes of a record and returns it as get_record does, or the validation errors '
            . 'per attribute. Pass the "updated_at" get_record answered to refuse the save when someone else has '
            . 'edited the record since.';
    }

    #[Override]
    public function getResources(): array
    {
        return array_values(array_filter(
            parent::getResources(),
            fn (Resource $resource): bool => !$resource->isReadOnly(),
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
                'attributes' => ['type' => 'object'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
            'required' => ['resource', 'id', 'attributes'],
            'additionalProperties' => false,
        ];
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return false;
    }

    #[Override]
    protected function run(array $arguments): array
    {
        $resource = $this->findResource($arguments, true);
        $record = $this->findRecord($resource, $arguments['id']);

        /** @var array<string, mixed> $attributes */
        $attributes = $arguments['attributes'];
        $this->loadAttributes($resource, $record, $attributes);

        if ($record instanceof StaleSaveInterface && is_string($arguments['updated_at'] ?? null)) {
            $loadedAt = $this->getTimestamp($arguments['updated_at']);
            $updatedAt = $record->getAttribute('updated_at');

            // Unchanged since the caller read it; the trail row of the last save may carry the next second
            if (!$updatedAt instanceof DateTimeInterface || $updatedAt->getTimestamp() !== $loadedAt) {
                $record->setAttributes(['loadedAt' => $loadedAt]);
            }
        }

        if ($record->update() === false) {
            throw new ToolException('The record was not saved.', array_filter([
                'errors' => $record->getErrors(),
                'updated_at' => $record->hasErrors('loadedAt') ? $this->formatValue($record->getAttribute('updated_at')) : null,
            ]));
        }

        return $this->getRecordData($resource, $record);
    }

    protected function getTimestamp(string $date): int
    {
        try {
            return (new DateTimeImmutable($date))->getTimestamp();
        } catch (Exception) {
            throw new ToolException("Invalid updated_at \"$date\".");
        }
    }
}
