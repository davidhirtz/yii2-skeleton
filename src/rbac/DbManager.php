<?php

namespace davidhirtz\yii2\skeleton\rbac;

use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use yii\rbac\Assignment;

class DbManager extends \yii\rbac\DbManager
{
    public $cache = 'cache';

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

    public function revoke($role, $userId): bool
    {
        $this->invalidateCache();

        if (($assignment = $this->getAssignment($role->name, $userId)) && parent::revoke($role, $userId)) {
            $this->createTrail(Trail::TYPE_REVOKE, $assignment, $userId);
            return true;
        }

        return false;
    }

    protected function createTrail(int $type, Assignment $assignment, int|string $userId): Trail
    {
        $trail = Trail::create();
        $trail->type = $type;
        $trail->model = User::class;
        $trail->model_id = (string)$userId;
        $trail->message = $this->getItem($assignment->roleName)->description ?? $assignment->roleName;
        $trail->insert();

        return $trail;
    }
}
