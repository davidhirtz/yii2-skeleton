<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Rbac;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use yii\rbac\Assignment;
use yii\rbac\Permission;
use yii\caching\CacheInterface;

class DbManager extends \yii\rbac\DbManager
{
    /**
     * @var CacheInterface|string|null
     */
    public $cache = 'cache';

    #[\Override]
    public function assign($role, $userId): ?Assignment
    {
        $this->invalidateCache();
        $assignment = $this->getAssignment($role->name, $userId);

        if (!$assignment) {
            $assignment = parent::assign($role, $userId);
            $this->createTrail(Trail::TYPE_ASSIGN, $assignment, $userId);
        }

        return $assignment;
    }

    #[\Override]
    public function revoke($role, $userId): bool
    {
        $this->invalidateCache();

        if (($assignment = $this->getAssignment($role->name, $userId)) && parent::revoke($role, $userId)) {
            $this->createTrail(Trail::TYPE_REVOKE, $assignment, $userId);
            return true;
        }

        return false;
    }

    /**
     * Answered from the cached hierarchy when there is one: the parent's four queries a user become the one that
     * reads the assignments, shared with {@see checkAccess()} — the user grids ask this for every row.
     *
     * @return array<string, Permission>
     */
    #[\Override]
    public function getPermissionsByUser($userId): array
    {
        $this->loadFromCache();

        if ($this->items === null || $this->parents === null || $this->isEmptyUserId($userId)) {
            return parent::getPermissionsByUser($userId);
        }

        $children = [];

        foreach ($this->parents as $child => $parents) {
            foreach ($parents as $parent) {
                $children[$parent][] = $child;
            }
        }

        $assignments = $this->checkAccessAssignments[(string)$userId] ??= $this->getAssignments($userId);
        $names = [];

        foreach (array_keys($assignments) as $name) {
            $names[$name] = true;
            $this->getChildrenRecursive((string)$name, $children, $names);
        }

        $permissions = [];

        foreach (array_keys($names) as $name) {
            $item = $this->items[$name] ?? null;

            if ($item instanceof Permission) {
                $permissions[$name] = $item;
            }
        }

        return $permissions;
    }

    /**
     * The item is named, never described: the description is a pointer that a later migration may rewrite, and the
     * row has to keep reading correctly when it does.
     */
    protected function createTrail(int $type, Assignment $assignment, int|string $userId): Trail
    {
        $item = $this->getItem($assignment->roleName);

        $trail = Trail::instantiateByType($type);
        $trail->model_class = User::class;
        $trail->model_id = (string)$userId;

        $trail->data = [
            'name' => $assignment->roleName,
            'type' => $item?->type,
        ];

        $trail->insert();

        return $trail;
    }
}
