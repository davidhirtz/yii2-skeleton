<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260913140000TokenExpiry extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->addColumn(
            User::tableName(),
            'verification_token_created_at',
            (string)$this->dateTime()->null()->after('verification_token')
        );

        $this->addColumn(
            User::tableName(),
            'password_reset_token_created_at',
            (string)$this->dateTime()->null()->after('password_reset_token')
        );

        // A token that is already out there keeps the age of the write that created it, so an old one is expired
        // from the first request after this migration rather than living on for another lifetime.
        $table = $this->getQuotedTableName(User::tableName());

        $this->execute("UPDATE $table SET [[verification_token_created_at]] = COALESCE([[updated_at]], [[created_at]])"
            . ' WHERE [[verification_token]] IS NOT NULL');

        $this->execute("UPDATE $table SET [[password_reset_token_created_at]] = COALESCE([[updated_at]], [[created_at]])"
            . ' WHERE [[password_reset_token]] IS NOT NULL');
    }

    public function safeDown(): void
    {
        $this->dropColumn(User::tableName(), 'password_reset_token_created_at');
        $this->dropColumn(User::tableName(), 'verification_token_created_at');
    }
}
