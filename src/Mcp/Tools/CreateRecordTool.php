<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Mcp\Resource;
use Override;
use Throwable;
use Yii;

class CreateRecordTool extends ResourceTool
{
    #[Override]
    public function getName(): string
    {
        return 'create_record';
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Creates a record and returns it as get_record does, or the validation errors per attribute. Call '
            . 'describe_resource first for the attributes of the type; a resource with a parent is created under one.';
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
                'type' => ['type' => 'integer', 'description' => 'The type id, the default type when it is not given.'],
                'parent' => ['type' => 'integer', 'description' => 'The id of the parent record, see describe_resource.'],
                'attributes' => ['type' => 'object'],
            ],
            'required' => ['resource', 'attributes'],
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
        $modelClass = $resource->getModelClass();
        $type = $arguments['type'] ?? $resource->getDefaultType();

        if (is_int($type) && (!is_subclass_of($modelClass, TypeAttributeInterface::class) || !$modelClass::findType($type))) {
            throw new ToolException("Unknown type $type for {$resource->getName()}.");
        }

        $record = $resource->instantiate(is_int($type) ? $type : null);
        $record->loadDefaultValues();

        if ($resource->getParentParam() !== null) {
            $parent = $this->findParent($resource, $arguments['parent'] ?? null);

            if (!$resource->populate($record, $parent)) {
                throw new ToolException('Parent not found: ' . $arguments['parent']);
            }
        }

        /** @var array<string, mixed> $attributes */
        $attributes = $arguments['attributes'];
        $this->loadAttributes($resource, $record, $attributes);

        $resource->beforeInsertRecord($record);

        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
            if (!$record->insert()) {
                $transaction->rollBack();
                $this->throwValidationErrors($record);
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            if ($transaction->getIsActive()) {
                $transaction->rollBack();
            }

            throw $exception;
        }

        return $this->getRecordData($resource, $record);
    }
}
