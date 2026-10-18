<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\SelectCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Validators\DateTimeValidator;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Validators\HexColorValidator;
use Hirtz\Skeleton\Validators\HtmlValidator;
use Hirtz\Skeleton\Validators\RelationValidator;
use stdClass;
use Yii;
use yii\base\Model;
use yii\validators\BooleanValidator;
use yii\validators\EmailValidator;
use yii\validators\NumberValidator;
use yii\validators\RangeValidator;
use yii\validators\StringValidator;
use yii\validators\UrlValidator;

/**
 * Describes the attributes a record of one type takes as a JSON Schema, read off the validators the record carries —
 * the same ones {@see AttributeTypecastBehavior} reads — and refined by the class of a custom attribute.
 */
class SchemaBuilder
{
    /**
     * @var list<string> never written by a caller: maintained by the record, its behaviors or a count updater
     */
    public array $excludedAttributes = [
        'created_at',
        'updated_at',
        'created_by_user_id',
        'updated_by_user_id',
        'loadedAt',
        'position',
        'type',
    ];

    /**
     * @param Resource<covariant ActiveRecord&AdminModelInterface> $resource
     */
    public function __construct(protected Resource $resource)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(ActiveRecord $record): array
    {
        return $this->buildObject($record, $this->getWritableAttributes($record));
    }

    /**
     * @return list<string>
     */
    public function getWritableAttributes(ActiveRecord $record): array
    {
        $excluded = [
            ...$this->excludedAttributes,
            ...$record::primaryKey(),
            ...$record instanceof TypeAttributeInterface ? $record->getType()?->getHiddenFields() ?? [] : [],
        ];

        $attributes = array_filter(
            $record->safeAttributes(),
            fn (string $name): bool => !in_array($name, $excluded, true)
                && !str_ends_with($name, '_count')
                && !$this->findCustomAttribute($record, $name) instanceof UploadCustomAttribute,
        );

        return $this->resource->filterAttributes($record, array_values($attributes));
    }

    /**
     * @param list<string> $attributes
     * @return array<string, mixed>
     */
    protected function buildObject(Model $model, array $attributes): array
    {
        $properties = [];
        $required = [];

        foreach ($attributes as $name) {
            $properties[$name] = $this->buildProperty($model, $name);

            $definition = $this->findCustomAttribute($model, $name);

            if ($definition ? $definition->isRequired($model) : $model->isAttributeRequired($name)) {
                $required[] = $name;
            }
        }

        return array_filter([
            'type' => 'object',
            'properties' => $properties ?: new stdClass(),
            'required' => $required,
            'additionalProperties' => false,
        ], fn (mixed $value): bool => $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildProperty(Model $model, string $name): array
    {
        $schema = ['title' => $model->getAttributeLabel($name)];

        if ($hint = trim(strip_tags($model->getAttributeHint($name)))) {
            $schema['description'] = $hint;
        }

        foreach ($model->getActiveValidators($name) as $validator) {
            $schema = [...$schema, ...$this->mapValidator($model, $name, $validator)];
        }

        $definition = $this->findCustomAttribute($model, $name);

        if ($definition !== null) {
            $schema = [...$schema, ...$this->mapCustomAttribute($model, $definition)];
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapValidator(Model $model, string $name, mixed $validator): array
    {
        return match (true) {
            $validator instanceof HexColorValidator => ['type' => 'string', 'pattern' => '^#(?:[0-9a-fA-F]{3}){1,2}$'],
            $validator instanceof StringValidator => array_filter([
                'type' => 'string',
                'minLength' => is_int($validator->min) ? $validator->min : null,
                'maxLength' => is_int($validator->max) ? $validator->max : null,
            ], fn (mixed $value): bool => $value !== null),
            $validator instanceof NumberValidator => array_filter([
                'type' => $validator->integerOnly ? 'integer' : 'number',
                'minimum' => is_int($validator->min) || is_float($validator->min) ? $validator->min : null,
                'maximum' => is_int($validator->max) || is_float($validator->max) ? $validator->max : null,
            ], fn (mixed $value): bool => $value !== null),
            $validator instanceof BooleanValidator => ['type' => 'boolean'],
            $validator instanceof DynamicRangeValidator => [
                ...$validator->integerOnly ? ['type' => 'integer'] : [],
                'enum' => $validator->getDynamicRange($model, $name),
            ],
            $validator instanceof RangeValidator => is_array($validator->range) && !$validator->not
                ? ($validator->allowArray
                    ? ['type' => 'array', 'items' => ['enum' => array_values($validator->range)]]
                    : ['enum' => array_values($validator->range)])
                : [],
            $validator instanceof EmailValidator => ['type' => 'string', 'format' => 'email'],
            $validator instanceof UrlValidator => $validator->when === null ? ['type' => 'string', 'format' => 'uri'] : [],
            $validator instanceof DateTimeValidator => $validator->type === AttributeTypecastBehavior::TYPE_DATE
                ? ['type' => 'string', 'format' => 'date']
                : [
                    'type' => 'string',
                    'format' => 'date-time',
                    '$comment' => 'Without an offset, the time is read in the time zone ' . Yii::$app->getTimeZone() . '.',
                ],
            $validator instanceof RelationValidator => ['type' => ['integer', 'null'], '$comment' => 'The id of the related record.'],
            $validator instanceof HtmlValidator => ['type' => 'string', 'contentMediaType' => 'text/html'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapCustomAttribute(Model $model, CustomAttribute $definition): array
    {
        if ($definition instanceof SelectCustomAttribute) {
            $enum = array_keys($definition->getOptions($model));

            return $definition->isMultiple()
                ? ['type' => 'array', 'items' => ['enum' => $enum]]
                : ['enum' => $enum];
        }

        if ($definition instanceof HtmlCustomAttribute) {
            return ['type' => 'string', 'contentMediaType' => 'text/html'];
        }

        if ($definition instanceof GroupCustomAttribute) {
            $item = $definition->createItem($model, '0');
            $object = $this->buildObject($item, array_values($item->safeAttributes()));

            return $definition->isMultiple()
                ? array_filter([
                    'type' => 'array',
                    'items' => $object,
                    'minItems' => $definition->getMinCount() ?: null,
                    'maxItems' => $definition->getMaxCount(),
                ], fn (mixed $value): bool => $value !== null)
                : $object;
        }

        return [];
    }

    protected function findCustomAttribute(Model $model, string $name): ?CustomAttribute
    {
        if (!$model instanceof CustomAttributeInterface) {
            return null;
        }

        foreach ($model->getCustomAttributeDefinitions() as $definition) {
            if (in_array($name, $definition->getAttributeNames($model), true)) {
                return $definition;
            }
        }

        return null;
    }
}
