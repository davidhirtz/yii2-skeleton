<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Models\Session;
use Hirtz\Skeleton\Models\UserLogin;
use yii\db\Expression;
use yii\db\Migration;

class M200414113006Ip extends Migration
{
    /**
     * Changes IPv4 fields "ip" to "ip_address" for implementations prior to
     * these changes in April 2020. Has no effect on newer versions.
     */
    public function safeUp(): void
    {
        foreach ([UserLogin::tableName(), Session::tableName()] as $table) {
            if ($this->getDb()->getSchema()->getTableSchema($table)->getColumn('ip')) {
                $this->addColumn($table, 'ip_address', 'VARBINARY(16) NULL AFTER [[ip]]');
                $this->update($table, ['ip_address' => new Expression('INET6_ATON(INET_NTOA([[ip]]))')]);
                $this->dropColumn($table, 'ip');
            }
        }
    }
}
