<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait;
use Yii;

/**
 * Class TypeAttributeTrait
 * @package davidhirtz\yii2\skeleton\db
 *
 * @property int $type
 */
trait TypeAttributeTrait
{
    /**
     * @param array $row
     * @return static
     */
    public static function instantiate($row)
    {
        if ($className = static::getTypes()[$row['type'] ?? null]['class'] ?? false) {
            return new $className(['type' => $row['type']]);
        }

        /** @noinspection PhpUndefinedClassInspection */
        return parent::instantiate($row);
    }

    /**
     * Override this method to implement types. The type array is must consist of a unique type as key and a associative
     * array containing at least a "name" key. Optional a "class" key can be set to instantiate a model on find and the
     * "icon" value will be used in {@link TypeGridViewTrait} on default.
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