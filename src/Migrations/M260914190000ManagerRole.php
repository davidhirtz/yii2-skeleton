<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * A role holds the permissions themselves, never another role: `admin` and the new `manager` both become a flat
 * list of every permission the installation has. The roles `admin` used to group them under are detached by the
 * bundles that own them.
 *
 * @noinspection PhpUnused
 */
class M260914190000ManagerRole extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $auth = $this->getAuthManager();

        // A fresh install has the role from the baseline already.
        $manager = $auth->getRole(User::AUTH_ROLE_MANAGER);

        if ($manager === null) {
            $manager = $auth->createRole(User::AUTH_ROLE_MANAGER);
            $auth->add($manager);
        }

        $admin = $auth->getRole(User::AUTH_ROLE_ADMIN);

        foreach ($auth->getPermissions() as $permission) {
            $this->addChildIfMissing($manager, $permission);
            $this->addChildIfMissing($admin, $permission);
        }

        $auth->invalidateCache();
    }

    /**
     * The permissions `admin` was flattened to stay: they grant exactly what it reached through a role, and
     * nothing records which of the two it held them by.
     */
    public function safeDown(): void
    {
        $auth = $this->getAuthManager();

        $auth->remove($auth->getRole(User::AUTH_ROLE_MANAGER));
        $auth->invalidateCache();
    }
}
