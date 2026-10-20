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
use Hirtz\Skeleton\Validators\HexColorValidator;
use Hirtz\Skeleton\Validators\Interfaces\JsonSchemaInterface;
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
use yii\validators\Validator;

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
        return $this->buildObject($record, $this->getWritableAttributes($record), $this->getReadOnlyAttributes($record));
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
     * @return list<string> custom attributes a caller reads and never writes: an upload, answered as its URL, and a
     * disabled or plain-text one
     */
    public function getReadOnlyAttributes(ActiveRecord $record): array
    {
        if (!$record instanceof CustomAttributeInterface) {
            return [];
        }

        $attributes = [];

        foreach ($record->getCustomAttributeDefinitions() as $definition) {
            if (
                $definition->isVisible($record)
                && ($definition instanceof UploadCustomAttribute || $definition->isDisabled($record) || $definition->isPlainText($record))
            ) {
                $attributes = [...$attributes, ...$definition->getAttributeNames($record)];
            }
        }

        return $this->resource->filterAttributes($record, $attributes);
    }

    /**
     * @param list<string> $attributes
     * @param list<string> $readOnly
     * @return array<string, mixed>
     */
    protected function buildObject(Model $model, array $attributes, array $readOnly = []): array
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

        foreach ($readOnly as $name) {
            $definition = $this->findCustomAttribute($model, $name);

            if ($definition !== null) {
                $properties[$name] = $this->buildReadOnlyProperty($model, $name, $definition);
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
        $descriptions = [$this->getHint($model, $name)];

        $definition = $this->findCustomAttribute($model, $name);
        $mapped = array_map(fn (mixed $validator): array => $this->mapValidator($model, $name, $validator), $model->getActiveValidators($name));

        if ($definition !== null) {
            $mapped[] = $this->mapCustomAttribute($model, $definition);
        }

        foreach ($mapped as $properties) {
            $descriptions[] = $properties['description'] ?? null;
            unset($properties['description']);

            $schema = [...$schema, ...$properties];
        }

        // A column's type only fills in for a validator naming none
        if (!isset($schema['type']) && !isset($schema['enum']) && $definition === null) {
            $schema = [...$schema, ...$this->mapColumn($model, $name)];
        }

        $description = implode(' ', array_filter($descriptions));

        return $description !== ''
            ? ['title' => $schema['title'], 'description' => $description, ...$schema]
            : $schema;
    }

    /**
     * A disabled or plain-text attribute carries no validator but `safe`, so its type is read off the rules it has
     * when enabled.
     *
     * @return array<string, mixed>
     */
    protected function buildReadOnlyProperty(Model $model, string $name, CustomAttribute $definition): array
    {
        $schema = ['title' => $model->getAttributeLabel($name)];

        if ($definition instanceof UploadCustomAttribute) {
            return [...$schema, 'type' => ['string', 'null'], 'format' => 'uri', 'readOnly' => true];
        }

        foreach ((clone $definition)->disabled(false)->plainText(false)->getRules($model) as $rule) {
            $type = $rule[1] ?? null;

            if (is_string($type)) {
                $validator = Validator::createValidator($type, $model, (array)$rule[0], array_slice($rule, 2, null, true));
                $schema = [...$schema, ...$this->mapValidator($model, $name, $validator)];
            }
        }

        unset($schema['description']);

        return [...$schema, 'readOnly' => true];
    }

    /**
     * A sentence holding a placeholder (`Now: {count}.`) is filled in by the form's script, so it is left out.
     */
    protected function getHint(Model $model, string $name): ?string
    {
        $hint = trim(strip_tags($model->getAttributeHint($name)));
        $hint = trim((string)preg_replace('/[^.!?]*\{\w+\}[^.!?]*[.!?]?/u', '', $hint));

        return $hint !== '' ? $hint : null;
    }

    /**
     * Lists the values of an enum with their names, which the ids alone do not say.
     *
     * @param array<int|string, string> $names
     */
    public static function describeEnum(array $names): string
    {
        $values = [];

        foreach ($names as $value => $name) {
            $values[] = "$value: $name";
        }

        return 'Values: ' . implode(', ', $values) . '.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapColumn(Model $model, string $name): array
    {
        $column = $model instanceof ActiveRecord ? $model::getTableSchema()->getColumn($name) : null;

        if ($column === null) {
            return [];
        }

        $type = match ($column->phpType) {
            'integer' => 'integer',
            'double' => 'number',
            'boolean' => 'boolean',
            'string' => 'string',
            'array' => 'array',
            default => null,
        };

        if ($type === null) {
            return [];
        }

        return ['type' => $column->allowNull ? [$type, 'null'] : $type];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapValidator(Model $model, string $name, mixed $validator): array
    {
        return match (true) {
            $validator instanceof JsonSchemaInterface => $validator->getJsonSchema($model, $name),
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
