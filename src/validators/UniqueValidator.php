<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\validators;

use Hirtz\Skeleton\db\ActiveRecord;
use Yii;

class UniqueValidator extends \yii\validators\UniqueValidator
{
    /**
     * Extends the default `unique` validator by adding a default `when` check, that prevents database queries when
     * the attributes haven't changed.
     */
    #[\Override]
    public function init(): void
    {
        $this->when ??= function (ActiveRecord $model, $attribute): bool {
            if (is_array($this->targetAttribute) && count($this->targetAttribute) > 1) {
                return count($model->getDirtyAttributes($this->targetAttribute)) > 0;
            }

            return $model->hasChangedAttributes((array)($this->targetAttribute ?: $attribute));
        };

        if (!$this->message) {
            $this->message = Yii::t('yii', '{attribute} "{value}" has already been taken.');
        }

        parent::init();
    }
}
