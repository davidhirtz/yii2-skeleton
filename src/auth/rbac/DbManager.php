<?php

namespace davidhirtz\yii2\skeleton\auth\rbac;

use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use yii\rbac\Assignment;

/**
 * Class DbManager
 * @package davidhirtz\yii2\skeleton\auth\rbac
 */
class DbManager extends \yii\rbac\DbManager
{
    /**
     * @var string
     */
    public $cache = 'cache';

    /**
     * @inheritDoc
     */
    public function assign($role, $userId)
    {
        $this->invalidateCache();
        $assignment = $this->getAssignment($role->name, $userId);

        if (!$assignment && ($assignment = parent::assign($role, $userId))) {
            $this->createTrail(Trail::TYPE_ASSIGN, $assignment, $userId);
        }

        return $assignment;
    }

    /**
     * @inheritDoc
     */
    public function revoke($role, $userId)
    {
        $this->invalidateCache();

        if (($assignment = $this->getAssignment($role->name, $userId)) && parent::revoke($role, $userId)) {
            $this->createTrail(Trail::TYPE_REVOKE, $assignment, $userId);
            return true;
        }

        return false;
    }

    private function createTrail(int $type, Assignment $assignment, int $userId): Trail
    {
        $trail = Trail::create();
        $trail->type = $type;
        $trail->model = User::class;
        $trail->model_id = $userId;
        $trail->message = $this->getItem($assignment->roleName)->description ?? $assignment->roleName;
        $trail->insert();

        return $trail;
    }
}