<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Rbac;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use yii\rbac\Assignment;
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
