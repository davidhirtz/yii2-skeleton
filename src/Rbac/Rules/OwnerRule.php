<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Rbac\Rules;

use Hirtz\Skeleton\Models\User;
use yii\rbac\Rule;

class OwnerRule extends Rule
{
    public $name = 'userUpdateRule';

    public function execute($user, $item, $params): bool
    {
        $userId = $user;

        /** @var User|null $user */
        $user = $params['user'] ?? null;
        return $user === null || !$user->isOwner() || $user->id === $userId;
    }
}
