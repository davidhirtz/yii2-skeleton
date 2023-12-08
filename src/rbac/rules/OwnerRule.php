<?php

namespace davidhirtz\yii2\skeleton\rbac\rules;

use davidhirtz\yii2\skeleton\models\User;
use yii\rbac\Rule;

class OwnerRule extends Rule
{
    public $name = 'userUpdateRule';

    public function execute($user, $item, $params): bool
    {
        $userId = $user;

        /** @var User|null $user */
        $user = $params['user'] ?? null;
        return $user === null || !$user->isOwner() || $user->id == $userId;
    }
}
