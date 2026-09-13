<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Rbac\Rules\OwnerRule;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260913150000UserDeleteRule extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $authManager = $this->getAuthManager();

        // The rule itself changed, so the serialized one in the table has to be replaced either way
        $rule = new OwnerRule();
        $authManager->update($rule->name, $rule);

        $userDelete = $authManager->getPermission(User::AUTH_USER_DELETE);

        if ($userDelete && !$userDelete->ruleName) {
            $userDelete->ruleName = $rule->name;
            $authManager->update($userDelete->name, $userDelete);
        }
    }

    public function safeDown(): void
    {
        $authManager = $this->getAuthManager();
        $userDelete = $authManager->getPermission(User::AUTH_USER_DELETE);

        if ($userDelete?->ruleName) {
            $userDelete->ruleName = null;
            $authManager->update($userDelete->name, $userDelete);
        }
    }
}
