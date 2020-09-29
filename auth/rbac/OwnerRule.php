<?php

namespace davidhirtz\yii2\skeleton\auth\rbac;

use davidhirtz\yii2\skeleton\models\User;
use yii\rbac\Rule;

/**
 * Class OwnerRule
 *
 * Checks if the user record can be updated. If no user record was provided via `$params` the rule will return `true` so
 * permissions like `userUpdate` and `userDelete` can be used in access control management.
 *
 * @package davidhirtz\yii2\skeleton\auth\rbac
 */
class OwnerRule extends Rule
{
    /**
     * @var string
     */
    public $name = 'userUpdateRule';

    /**
     * @inheritDoc
     */
    public function execute($userId, $item, $params)
    {
        /** @var User $user */
        $user = $params['user'] ?? null;
        return $user === null || !$user->isOwner() || $user->id == $userId;
    }
}