<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use Yii;

/**
 * TypeAttributeTrait implements type attribute methods and validation and  for an active record. It can also instantiate
 * a custom model class based on the `class` key defined in the type options of {@link static::getTypes()}.
 *
 * @property int $type
 */
trait TypeAttributeTrait
{
    /**
     * @var static[][]
     */
    private static $_instances = [];

    /**
     * Instantiates a class based on the given `type`. In contrast to the original implementation, this can be used  for
     * creating new records directly, as it also populates the model.
     *
     * @param array $row
     * @return static
     */
    public static function instantiate($row)
    {
        $className = static::getTypes()[$row['type'] ?? null]['class'] ?? static::class;
        return new $className($row);
    }

    /**
     * Override this method to implement types. The type array is must consist of a unique type as key and an associative
     * array containing at least a "name" key. Optional a "class" key can be set to instantiate a model on find and the
     * "icon" value will be used in {@link \davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait} on default.
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            static::TYPE_DEFAULT => [
                'name' => Yii::t('skeleton', 'Default'),
            ],
        ];
    }

    /**
     * Returns an array containing all instances set via the "class" option in `getTypes`.
     * @return static[]
     */
    public static function getTypeInstances()
    {
        if (!isset(static::$_instances[static::class])) {
            static::$_instances[static::class] = [];

            foreach (static::getTypes() as $type => $typeOptions) {
                static::$_instances[static::class][$type] = static::instantiate(['type' => $type]);
                static::$_instances[static::class][$type]->type = $type;
            }
        }

        return static::$_instances[static::class];
    }

    /**
     * @return string
     */
    public function getTypeName(): string
    {
        return $this->getTypeOptions()['name'] ?? '';
    }

    /**
     * @return string|null
     */
    public function getTypeIcon(): string
    {
        return static::getTypes()[$this->type]['icon'] ?? '';
    }

    /**
     * @return array
     */
    public function getTypeOptions(): array
    {
        return static::getTypes()[$this->type] ?? [];
    }
}