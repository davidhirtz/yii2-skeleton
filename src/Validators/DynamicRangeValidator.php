<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators;

use Hirtz\Skeleton\Mcp\SchemaBuilder;
use Hirtz\Skeleton\Models\Definitions\Definition;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Validators\Interfaces\JsonSchemaInterface;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\validators\RangeValidator;

class DynamicRangeValidator extends RangeValidator implements JsonSchemaInterface
{
    public bool $integerOnly = true;

    /**
     * An empty array rather than none, since {@see RangeValidator} throws without one.
     *
     * @var array<int|string, mixed>|\Closure|\Traversable<int|string, mixed>
     */
    public $range = [];

    #[\Override]
    public function validateAttribute($model, $attribute): void
    {
        $this->range = $this->getDynamicRange($model, $attribute);
        parent::validateAttribute($model, $attribute);

        if ($this->integerOnly && !$model->hasErrors($attribute)) {
            $model->{$attribute} = is_numeric($value = $model->{$attribute}) ? (int)$value : $value;
        }
    }

    #[\Override]
    public function clientValidateAttribute($model, $attribute, $view): null|string
    {
        $this->range = $this->getDynamicRange($model, $attribute);
        return parent::clientValidateAttribute($model, $attribute, $view);
    }

    /**
     * Reads the definitions, keyed by value unlike the list {@see TypeAttributeInterface::getTypes()} returns; a
     * {@see Type} the record cannot take is out of range.
     *
     * @return array<int|string, mixed>
     */
    public function getDynamicRange(Model $model, string $attribute): array
    {
        return array_keys($this->getDefinitions($model, $attribute));
    }

    #[\Override]
    public function getJsonSchema(Model $model, string $attribute): array
    {
        $definitions = $this->getDefinitions($model, $attribute);
        $names = array_filter(array_map(fn (mixed $definition): ?string => $definition instanceof Definition ? $definition->getName() : null, $definitions));

        return array_filter([
            'type' => $this->integerOnly ? 'integer' : null,
            'enum' => array_keys($definitions),
            'description' => $names ? SchemaBuilder::describeEnum($names) : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getDefinitions(Model $model, string $attribute): array
    {
        $method = 'get' . Inflector::camelize($attribute) . 'Definitions';

        if (!$model->hasMethod($method)) {
            $method = 'get' . Inflector::camelize(Inflector::pluralize($attribute));
        }

        if (!$model->hasMethod($method)) {
            throw new InvalidConfigException($model::class . '::' . $method . '() must be defined to use ' . self::class . '.');
        }

        $range = [];

        /** @var iterable<int|string, mixed> $definitions */
        $definitions = $model->{$method}();

        foreach ($definitions as $value => $definition) {
            if (!$definition instanceof Type || $definition->isAvailableOrStored($model, $attribute)) {
                $range[$value] = $definition;
            }
        }

        return $range;
    }
}
