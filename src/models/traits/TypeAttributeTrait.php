<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use Yii;

/**
 * TypeAttributeTrait implements type attribute methods and validation and for an active record. It can also instantiate
 * a custom model class based on the `class` key defined in the type options of {@see static::getTypes()}.
 *
 * @property int $type
 */
trait TypeAttributeTrait
{
    /**
     * @var static[][]
     */
    private static ?array $_instances = [];

    /**
     * Instantiates a class based on the given `type`. In contrast to the original implementation, this can be used for
     * creating new records directly, as it also populates the model.
     */
    public static function instantiate($row): static
    {
        $className = static::getTypes()[$row['type'] ?? null]['class'] ?? static::class;

        $model = $className::create();
        $model->setAttributes($row, false);

        return $model;
    }

    /**
     * Override this method to implement types. The type array must consist of a unique type as key and an associative
     * array containing at least a "name" key. Optional a "class" key can be set to instantiate a model on find, and the
     * "icon" value will be used in {@see TypeGridViewTrait} on default.
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
    public static function getTypeInstances(): array
    {
        if (!isset(self::$_instances[static::class])) {
            self::$_instances[static::class] = [];

            foreach (static::getTypes() as $type => $typeOptions) {
                self::$_instances[static::class][$type] = static::instantiate(['type' => $type]);
                self::$_instances[static::class][$type]->type = $type;
            }
        }

        return self::$_instances[static::class];
    }

    public function getTypeName(): string
    {
        return $this->getTypeOptions()['name'] ?? '';
    }

    public function getTypeIcon(): string
    {
        return static::getTypes()[$this->type]['icon'] ?? '';
    }

    public function getTypeOptions(): array
    {
        return static::getTypes()[$this->type] ?? [];
    }
}
