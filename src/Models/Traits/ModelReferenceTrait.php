<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Yii;
use yii\base\Model;

/**
 * A row naming an owner (`model_class`, `model_id`) and the attributes using something in it, nested names joined by
 * `.`.
 *
 * @property string $model_class
 * @property int $model_id
 * @property array<string, int>|null $attribute_counts
 */
trait ModelReferenceTrait
{
    /**
     * The owner, `null` when it is gone or its class is.
     */
    public function getModel(): ?ActiveRecord
    {
        if (!$this->isRelationPopulated('model')) {
            static::populateModelRelations([$this]);
        }

        $model = $this->getRelatedRecords()['model'] ?? null;

        return $model instanceof ActiveRecord ? $model : null;
    }

    public function getMissingModelName(): string
    {
        return "$this->model_class #$this->model_id";
    }

    public function populateModelRelation(?ActiveRecord $model): void
    {
        $this->populateRelation('model', $model);
    }

    /**
     * One query per class; `$searchable` loads what a searchable owner's heading reads (a section's entry).
     *
     * @param array<static> $references
     */
    public static function populateModelRelations(array $references, bool $searchable = false): void
    {
        $modelIds = [];

        foreach ($references as $reference) {
            if (!$reference->isRelationPopulated('model')) {
                $modelIds[$reference->model_class][$reference->model_id] = $reference->model_id;
            }
        }

        foreach ($modelIds as $modelClass => $ids) {
            $models = [];

            if (class_exists($modelClass) && is_subclass_of($modelClass, ActiveRecord::class)) {
                $instance = Yii::createObject($modelClass);
                $query = $searchable && $instance instanceof SearchableInterface ? $instance::findSearchable() : $instance::find();

                $models = $query->andWhere(['id' => array_values($ids)])
                    ->indexBy('id')
                    ->all();
            }

            foreach ($references as $reference) {
                if ($reference->model_class === $modelClass && !$reference->isRelationPopulated('model')) {
                    $model = $models[$reference->model_id] ?? null;
                    $reference->populateModelRelation($model instanceof ActiveRecord ? $model : null);
                }
            }
        }
    }

    /**
     * By the owner's labels (`Gallery › Image`, `Content (DE)`).
     *
     * @return array<string, int>
     */
    public function getAttributeLabelCounts(): array
    {
        $model = $this->getModel();
        $labels = [];

        foreach ($this->attribute_counts ?? [] as $name => $count) {
            $path = explode('.', (string)$name);
            $label = $model ? implode(' › ', static::getPathLabels($model, $path)) : (string)$name;
            $labels[$label] = ($labels[$label] ?? 0) + (int)$count;
        }

        return $labels;
    }

    /**
     * @param list<string> $path
     * @return list<string>
     */
    protected static function getPathLabels(Model $owner, array $path): array
    {
        $name = array_shift($path);

        if ($name === null) {
            return [];
        }

        $label = $owner->getAttributeLabel($name);
        $definition = $owner instanceof CustomAttributeInterface ? $owner->getCustomAttribute($name) : null;

        if (!$path) {
            return [$label];
        }

        return $definition instanceof GroupCustomAttribute
            ? [$label, ...static::getPathLabels($definition->createItem($owner, '0'), $path)]
            : [$label, ...$path];
    }

    /**
     * @param list<string> $path
     * @return array{CustomAttribute|null, Model|null}
     */
    protected static function findDefinition(Model $owner, array $path): array
    {
        $name = array_shift($path);
        $definition = $name !== null && $owner instanceof CustomAttributeInterface
            ? $owner->getCustomAttribute($name)
            : null;

        if (!$path) {
            return [$definition, $owner];
        }

        return $definition instanceof GroupCustomAttribute
            ? static::findDefinition($definition->createItem($owner, '0'), $path)
            : [null, null];
    }
}
