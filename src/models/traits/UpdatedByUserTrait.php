<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;

/**
 * @property int|null $updated_by_user_id
 * @property-read User|null $updated {@see static::getUpdated()}
 */
trait UpdatedByUserTrait
{
    /**
     * @return UserQuery<User>
     */
    public function getUpdated(): UserQuery
    {
        /** @var UserQuery $query */
        $query = $this->hasOne(User::class, ['id' => 'updated_by_user_id']);
        return $query;
    }

    public function populateUpdatedRelation(?User $user): void
    {
        $this->populateRelation('updated', $user);
        $this->updated_by_user_id = $user?->id;
    }
}
