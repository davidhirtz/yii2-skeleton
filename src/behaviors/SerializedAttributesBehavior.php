<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use yii\base\Behavior;

/**
 * Class SerializedAttributesBehavior.
 * @package davidhirtz\yii2\skeleton\behaviors
 *
 * @property \davidhirtz\yii2\skeleton\db\ActiveRecord $owner
 */
class SerializedAttributesBehavior extends Behavior
{
    /**
     * @var string[]
     */
    public $attributes = [];

    /**
     * @var bool serialized data to protect them from corruption (when your DB is not in UTF-8)
     * @see http://www.jackreichert.com/2014/02/02/handling-a-php-unserialize-offset-error/
     */
    public $encode = false;

    private array $oldAttributes = [];

    /***********************************************************************
     * Events.
     ***********************************************************************/

    /**
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'serializeAttributes',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'serializeAttributes',
            ActiveRecord::EVENT_AFTER_INSERT => 'unserializeAttributes',
            ActiveRecord::EVENT_AFTER_UPDATE => 'unserializeAttributes',
            ActiveRecord::EVENT_AFTER_FIND => 'unserializeAttributes',
        ];
    }

    /**
     * Serializes attributes.
     */
    public function serializeAttributes()
    {
        foreach ($this->attributes as $attribute) {
            if (isset($this->oldAttributes[$attribute])) {
                $this->owner->setOldAttribute($attribute, $this->oldAttributes[$attribute]);
            }

            if (is_array($this->owner->{$attribute}) && count($this->owner->{$attribute}) > 0) {
                $this->owner->{$attribute} = serialize($this->owner->{$attribute});

                if ($this->encode) {
                    $this->owner->{$attribute} = base64_encode($this->owner->{$attribute});
                }
            } else {
                $this->owner->$attribute = null;
            }
        }
    }

    /**
     * Unserializes attributes.
     */
    public function unserializeAttributes()
    {
        foreach ($this->attributes as $attribute) {
            $this->oldAttributes[$attribute] = $this->owner->getOldAttribute($attribute);

            if (is_scalar($this->owner->{$attribute})) {
                if ($this->encode) {
                    $this->owner->$attribute = base64_decode($this->owner->{$attribute});
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
