<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Rbac\Rules;

use Hirtz\Skeleton\Models\User;
use Yii;
use yii\rbac\Rule;

/**
 * Guards the site owner, and anyone whose permissions the acting user does not already hold: `userUpdate` can set
 * a password, generate a reset token and clear a second factor, so without this it is a takeover of every account
 * it reaches — including the ones that can grant permissions.
 */
class OwnerRule extends Rule
{
    public $name = 'userUpdateRule';

    /**
     * @var array<int|string, list<string>> the permission names already looked up in this request
     */
    private array $permissionNames = [];

    public function execute($user, $item, $params): bool
    {
        /** @var User|null $target */
        $target = $params['user'] ?? null;

        if ($target === null || $target->id === $user) {
            return true;
        }

        if ($target->isOwner()) {
            return false;
        }

        return !$this->hasUnheldPermissions($target->id, $user);
    }

    private function hasUnheldPermissions(int|string $targetId, int|string $userId): bool
    {
        $held = $this->getPermissionNames($userId);

        // Nobody holds more than everything, so the usual case — an administrator — costs no lookup at all
        if (count($held) >= count(Yii::$app->getAuthManager()->getPermissions())) {
            return false;
        }

        return (bool)array_diff($this->getPermissionNames($targetId), $held);
    }

    /**
     * @return list<string>
     */
    private function getPermissionNames(int|string $userId): array
    {
        return $this->permissionNames[$userId] ??= array_keys(
            Yii::$app->getAuthManager()->getPermissionsByUser($userId)
        );
    }
}
