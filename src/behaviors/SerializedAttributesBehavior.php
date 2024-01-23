<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use yii\base\Behavior;

/**
 * @property ActiveRecord $owner
 */
class SerializedAttributesBehavior extends Behavior
{
    /**
     * @var array containing the attributes to be serialized
     */
    public array $attributes = [];

    /**
     * @var bool serialized data to protect them from corruption (when your DB is not in UTF-8)
     * @see http://www.jackreichert.com/2014/02/02/handling-a-php-unserialize-offset-error/
     */
    public bool $encode = false;

    private array $oldAttributes = [];

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => $this->serializeAttributes(...),
            ActiveRecord::EVENT_BEFORE_UPDATE => $this->serializeAttributes(...),
            ActiveRecord::EVENT_AFTER_INSERT => $this->unserializeAttributes(...),
            ActiveRecord::EVENT_AFTER_UPDATE => $this->unserializeAttributes(...),
            ActiveRecord::EVENT_AFTER_FIND => $this->unserializeAttributes(...),
        ];
    }

    public function serializeAttributes(): void
    {
        foreach ($this->attributes as $attribute) {
            if (isset($this->oldAttributes[$attribute])) {
                $this->owner->setOldAttribute($attribute, $this->oldAttributes[$attribute]);
            }

            if ($this->owner->$attribute) {
                $this->owner->$attribute = serialize($this->owner->$attribute);

                if ($this->encode) {
                    $this->owner->$attribute = base64_encode($this->owner->$attribute);
                }
            } else {
                $this->owner->$attribute = null;
            }
        }
    }

    public function unserializeAttributes(): void
    {
        foreach ($this->attributes as $attribute) {
            $this->oldAttributes[$attribute] = $this->owner->getOldAttribute($attribute);

            if (is_scalar($this->owner->$attribute)) {
                if ($this->encode) {
                    $this->owner->$attribute = base64_decode($this->owner->$attribute);
                }

                $value = @unserialize($this->owner->$attribute);

                if ($value !== false) {
                    $this->owner->setAttribute($attribute, $value);
                    $this->owner->setOldAttribute($attribute, $value);
                    continue;
                }
            }

            $this->owner->setAttribute($attribute, []);
            $this->owner->setOldAttribute($attribute, []);
        }
    }
}
