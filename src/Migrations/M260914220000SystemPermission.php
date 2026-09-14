<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use yii\db\Migration;

/**
 * The one thing `admin` holds and `manager` does not, named rather than left to a role check — so the permission
 * list a role shows is the whole of what it can do.
 *
 * @noinspection PhpUnused
 */
class M260914220000SystemPermission extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->addPermission(Module::AUTH_SYSTEM, $this->getDescription(), User::AUTH_ROLE_ADMIN);
    }

    public function safeDown(): void
    {
        $auth = $this->getAuthManager();

        $auth->remove($auth->getPermission(Module::AUTH_SYSTEM));
        $auth->invalidateCache();
    }

    private function getDescription(): Message
    {
        return Message::make('skeleton', 'AUTH_SYSTEM_DESCRIPTION');
    }
}
