<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;

/**
 * @property int $updated_by_user_id
 * @property-read User $updated {@see static::getUpdated()}
 */
trait UpdatedByUserTrait
{
    public function getUpdated(): UserQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(User::class, ['id' => 'updated_by_user_id']);
    }

    public function populateUpdatedRelation(?User $user): void
    {
        $this->populateRelation('updated', $user);
        $this->updated_by_user_id = $user?->id;
    }
}