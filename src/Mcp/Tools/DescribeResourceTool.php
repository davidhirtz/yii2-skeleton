<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Hirtz\Skeleton\Mcp\SchemaBuilder;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Override;
use Yii;

class DescribeResourceTool extends ResourceTool
{
    #[Override]
    public function getName(): string
    {
        return 'describe_resource';
    }

    #[Override]
    public function getTitle(): string
    {
        return 'Describe a resource';
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Describes a resource: the JSON Schema of the attributes create_record and update_record take for a '
            . 'type, the types and statuses with their ids, and the content languages. A translated attribute has '
            . 'one property per language, the source language without a suffix (name, name_de, …). A readOnly '
            . 'property is answered by get_record, a file as its URL, and never written.';
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'resource' => $this->getResourceProperty(),
                'type' => [
                    'type' => 'integer',
                    'description' => 'The type id, the default type when it is not given.',
                ],
            ],
            'required' => ['resource'],
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
        $modelClass = $resource->getModelClass();

        $type = $arguments['type'] ?? $resource->getDefaultType();
        $types = is_subclass_of($modelClass, TypeAttributeInterface::class) ? $modelClass::getTypeDefinitions() : [];

        if (is_int($type) && !isset($types[$type])) {
            throw new ToolException("Unknown type $type for {$resource->getName()}.");
        }

        $record = $resource->instantiate(is_int($type) ? $type : null);
        $record->loadDefaultValues();

        $data = [
            'resource' => $resource->getName(),
            'label' => $record->getAdminType(),
            'writable' => !$resource->isReadOnly(),
            'creatable' => $resource->isCreatable(),
            'reorderable' => $resource->isReorderable(),
        ];

        if ($types) {
            $data['type'] = $record instanceof TypeAttributeInterface ? $record->getAttribute('type') : null;
            $data['types'] = [];

            foreach ($types as $value => $definition) {
                $data['types'][] = ['id' => $value, 'name' => $definition->getName()];
            }
        }

        if ($record instanceof StatusAttributeInterface) {
            $data['statuses'] = [];

            foreach ($record::getStatusDefinitions() as $value => $definition) {
                $data['statuses'][] = ['id' => $value, 'name' => $definition->getName()];
            }
        }

        if ($param = $resource->getParentParam()) {
            $data['parent'] = [
                'param' => $param,
                'resource' => $this->findParentResourceName($resource->getParentClass()),
                'required' => $resource->isParentRequired(),
            ];
        }

        if ($record instanceof I18nAttributeInterface && $record->getI18nAttributes()) {
            $i18n = Yii::$app->getI18n();
            $data['languages'] = [];

            foreach ($i18n->getLanguages() as $language) {
                $data['languages'][] = [
                    'language' => $language,
                    'label' => $i18n->getLabel($language),
                    'suffix' => substr($i18n->getAttributeName('x', $language), 1),
                ];
            }
        }

        $data['attributes'] = (new SchemaBuilder($resource))->build($record);

        return $data;
    }

    protected function findParentResourceName(?string $parentClass): ?string
    {
        foreach ($this->mcp->getResources() as $resource) {
            if ($parentClass !== null && is_a($resource->getModelClass(), $parentClass, true)) {
                return $resource->getName();
            }
        }

        return null;
    }
}
