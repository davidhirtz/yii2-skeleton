<?php

namespace davidhirtz\yii2\skeleton\db;

use Yii;

/**
 * Class TypeAttributeTrait.
 * @package davidhirtz\yii2\skeleton\db
 *
 * @property int $type
 */
trait TypeAttributeTrait
{
    /**
     * Checks if type is set as a key of {@link TypeAttributeTrait::getTypes()}.
     * @deprecated Please use {@link \davidhirtz\yii2\skeleton\validators\DynamicRangeValidator}
     */
    public function validateType()
    {
        if ($this->type === null) {
            $this->type = static::TYPE_DEFAULT;

        } elseif (static::getTypes() && !in_array($this->type, array_keys(static::getTypes()))) {
            $this->addInvalidAttributeError('type');
        }

        $this->type = (int)$this->type;
    }

    /**
     * Override this method to implement types.
     * @return array containing the type as key and a associative array containing at least "name".
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