<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;
use yii\db\Query;

/**
 * @noinspection PhpUnused
 */
class M260913160000TwoFactorAuthentication extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->alterColumn(User::tableName(), 'google_2fa_secret', (string)$this->string()->null());

        $this->addColumn(
            User::tableName(),
            'google_2fa_recovery_codes',
            (string)$this->json()->null()->after('google_2fa_secret')
        );

        foreach ($this->getUsersWithSecret() as $id => $secret) {
            $this->update(
                User::tableName(),
                ['google_2fa_secret' => User::encryptTwoFactorAuthenticationSecret($secret)],
                ['id' => $id]
            );
        }
    }

    public function safeDown(): void
    {
        foreach ($this->getUsersWithSecret() as $id => $secret) {
            $this->update(
                User::tableName(),
                ['google_2fa_secret' => User::decryptTwoFactorAuthenticationSecret($secret)],
                ['id' => $id]
            );
        }

        $this->dropColumn(User::tableName(), 'google_2fa_recovery_codes');
        $this->alterColumn(User::tableName(), 'google_2fa_secret', (string)$this->string(16)->null());
    }

    /**
     * @return array<int, string>
     */
    private function getUsersWithSecret(): array
    {
        $rows = (new Query())
            ->select(['id', 'google_2fa_secret'])
            ->from(User::tableName())
            ->where(['not', ['google_2fa_secret' => null]])
            ->all($this->getDb());

        return array_column($rows, 'google_2fa_secret', 'id');
    }
}
