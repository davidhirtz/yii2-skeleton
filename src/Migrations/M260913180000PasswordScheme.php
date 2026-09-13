<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use Yii;
use yii\db\Migration;
use yii\db\Query;

/**
 * @noinspection PhpUnused
 */
class M260913180000PasswordScheme extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->renameColumn(User::tableName(), 'password_salt', 'password_scheme');
        $this->invalidateLegacyPasswords();
    }

    public function safeDown(): void
    {
        $this->renameColumn(User::tableName(), 'password_scheme', 'password_salt');
    }

    /**
     * A v2 hash is bcrypt of the password plus a per-user salt, and keeping it would carry v2's five-character
     * minimum into v3 forever, unpeppered and unreachable by the new rules. Every one of them is dropped, which
     * leaves the account in the state a user created without a password is already in: no login until the reset
     * link is used. `upgrade/passwords` is what sends those links.
     */
    private function invalidateLegacyPasswords(): void
    {
        $ids = (new Query())
            ->select(['id'])
            ->from(User::tableName())
            ->where(['not', ['password_scheme' => null]])
            ->andWhere(['not', ['password_scheme' => User::PASSWORD_PEPPER]])
            ->column($this->getDb());

        foreach ($ids as $id) {
            $this->update(User::tableName(), [
                'password_hash' => null,
                'password_scheme' => null,
                // The remember-me cookies were issued against the old password
                'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
            ], ['id' => $id]);
        }

        if ($ids) {
            $count = count($ids);
            echo "    > $count legacy password(s) invalidated, run `upgrade/passwords` to mail the reset links.\n";
        }
    }
}
