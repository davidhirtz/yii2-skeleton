<?php

namespace davidhirtz\yii2\skeleton\db;

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
        return [];
    }

    /**
     * @return string
     */
    public function getTypeName(): string
    {
        return $this->getTypeOptions()['name'] ?? '';
    }


    /**
     * @return array
     */
    public function getTypeOptions(): array
    {
        return static::getTypes()[$this->type] ?? [];
    }
}