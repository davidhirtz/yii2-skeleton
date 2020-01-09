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
     * Status validator.
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
     * @return array
     */
    public static function getTypes(): array
    {
        return [];
    }

    /**
     * @return string|null
     */
    public function getTypeName(): string
    {
        return static::getTypes()[$this->type]['name'] ?? '';
    }
}