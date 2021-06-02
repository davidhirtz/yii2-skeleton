<?php

namespace davidhirtz\yii2\skeleton\validators;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use Yii;

/**
 * Class UniqueValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class UniqueValidator extends \yii\validators\UniqueValidator
{
    /**
     * Extends the default `unique` validator by adding a default `when` check, that prevents database queries when
     * the attributes haven't changed.
     */
    public function init()
    {
        if ($this->when === null) {
            $this->when = function (ActiveRecord $model) {
                if (is_array($this->targetAttribute) && count($this->targetAttribute) > 1) {
                    return count($model->getDirtyAttributes($this->targetAttribute)) > 0;
                }

                return $model->isAttributeChanged($this->targetAttribute);
            };
        }

        $this->message = Yii::t('yii', '{attribute} "{value}" has already been taken.');
        parent::init();
    }
}