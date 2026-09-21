<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Yii;
use yii\db\Migration;
use yii\db\JsonExpression;
use yii\db\Query;

/**
 * @noinspection PhpUnused
 */
class M260913170000UserToken extends Migration
{
    use MigrationTrait;

    /**
     * The lifetime as it stood when this migration was written, so the copy does not follow a later change to
     * {@see User::$tokenLifetime}.
     */
    private const int LEGACY_TOKEN_LIFETIME = 86400;

    public function safeUp(): void
    {
        // A fresh install has the user_token table from the baseline already.
        if ($this->hasTable(UserToken::tableName())) {
            return;
        }

        $this->createTable(UserToken::tableName(), [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'type' => $this->string(16)->notNull(),
            'token' => $this->string(64)->notNull(),
            'expires_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ], $this->getTableOptions());

        $this->createIndex('token', UserToken::tableName(), 'token', true);
        $this->createIndex('user_id', UserToken::tableName(), ['user_id', 'type']);

        $this->addForeignKey(
            'user_token_user_id_ibfk',
            UserToken::tableName(),
            'user_id',
            User::tableName(),
            'id',
            'CASCADE'
        );

        // Whether an address was confirmed is durable state. The token that proved it is not — it expires, and
        // garbage collection removes it — so the two cannot be the same column any more.
        $this->addColumn(User::tableName(), 'email_confirmed_at', (string)$this->dateTime()->null()->after('email'));

        $table = $this->getQuotedTableName(User::tableName());

        $this->execute("UPDATE $table SET [[email_confirmed_at]] = COALESCE([[updated_at]], [[created_at]])"
            . ' WHERE [[verification_token]] IS NULL');

        $this->copyTokens();

        $this->dropColumn(User::tableName(), 'google_2fa_recovery_codes');
        $this->dropColumn(User::tableName(), 'password_reset_token_created_at');
        $this->dropColumn(User::tableName(), 'password_reset_token');
        $this->dropColumn(User::tableName(), 'verification_token_created_at');
        $this->dropColumn(User::tableName(), 'verification_token');
    }

    public function safeDown(): void
    {
        $this->addColumn(User::tableName(), 'verification_token', (string)$this->string(32)->null());
        $this->addColumn(User::tableName(), 'verification_token_created_at', (string)$this->dateTime()->null());
        $this->addColumn(User::tableName(), 'password_reset_token', (string)$this->string(32)->null());
        $this->addColumn(User::tableName(), 'password_reset_token_created_at', (string)$this->dateTime()->null());
        $this->addColumn(User::tableName(), 'google_2fa_recovery_codes', (string)$this->json()->null());

        $this->restoreRecoveryCodes();

        // A verification or password reset token is only stored as its HMAC, so it cannot come back. The pending
        // links die and have to be requested again; what must survive is that the address is still unconfirmed,
        // which the reverted schema can only express as a token.
        $table = $this->getQuotedTableName(User::tableName());
        $rows = (new Query())
            ->select(['id'])
            ->from(User::tableName())
            ->where(['email_confirmed_at' => null])
            ->column($this->getDb());

        foreach ($rows as $id) {
            $this->update($table, [
                'verification_token' => Yii::$app->getSecurity()->generateRandomString(),
                'verification_token_created_at' => new DateTime(),
            ], ['id' => $id]);
        }

        $this->dropColumn(User::tableName(), 'email_confirmed_at');
        $this->dropTable(UserToken::tableName());
    }

    private function copyTokens(): void
    {
        $rows = (new Query())
            ->select([
                'id',
                'verification_token',
                'verification_token_created_at',
                'password_reset_token',
                'password_reset_token_created_at',
                'google_2fa_recovery_codes',
            ])
            ->from(User::tableName())
            ->where(['or',
                ['not', ['verification_token' => null]],
                ['not', ['password_reset_token' => null]],
                ['not', ['google_2fa_recovery_codes' => null]],
            ])
            ->all($this->getDb());

        foreach ($rows as $row) {
            if ($row['verification_token']) {
                $this->insertToken($row['id'], UserToken::TYPE_VERIFICATION, [
                    'token' => UserToken::hash($row['verification_token']),
                    'created_at' => $row['verification_token_created_at'],
                ]);
            }

            if ($row['password_reset_token']) {
                $this->insertToken($row['id'], UserToken::TYPE_PASSWORD_RESET, [
                    'token' => UserToken::hash($row['password_reset_token']),
                    'created_at' => $row['password_reset_token_created_at'],
                ]);
            }

            // The recovery codes were already stored as the same HMAC, so they move across as they are
            $recoveryCodes = $row['google_2fa_recovery_codes'];
            $recoveryCodes = is_string($recoveryCodes) ? json_decode($recoveryCodes, true) : $recoveryCodes;

            foreach (is_array($recoveryCodes) ? $recoveryCodes : [] as $hash) {
                $this->insertToken($row['id'], UserToken::TYPE_RECOVERY_CODE, [
                    'token' => $hash,
                    'created_at' => null,
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $columns
     */
    private function insertToken(int|string $userId, string $type, array $columns): void
    {
        $createdAt = $columns['created_at'] ?: gmdate('Y-m-d H:i:s');

        $this->insert(UserToken::tableName(), [
            'user_id' => $userId,
            'type' => $type,
            'token' => $columns['token'],
            'expires_at' => $type === UserToken::TYPE_RECOVERY_CODE
                ? null
                : gmdate('Y-m-d H:i:s', strtotime($createdAt) + self::LEGACY_TOKEN_LIFETIME),
            'created_at' => $createdAt,
        ]);
    }

    private function restoreRecoveryCodes(): void
    {
        $rows = (new Query())
            ->select(['user_id', 'token'])
            ->from(UserToken::tableName())
            ->where(['type' => UserToken::TYPE_RECOVERY_CODE])
            ->all($this->getDb());

        $codes = [];

        foreach ($rows as $row) {
            $codes[$row['user_id']][] = $row['token'];
        }

        foreach ($codes as $userId => $hashes) {
            // `update()` does not encode an array for a JSON column, and a json_encode()d string would be
            // encoded a second time — the expression is the only form that round-trips
            $this->update(User::tableName(), [
                'google_2fa_recovery_codes' => new JsonExpression($hashes),
            ], ['id' => $userId]);
        }
    }
}
