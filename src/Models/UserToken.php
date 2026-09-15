<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\SecretKey;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Queries\UserTokenQuery;
use Hirtz\Skeleton\Validators\RelationValidator;
use Override;
use Yii;

/**
 * A single-use credential that is mailed to, or written down by, a user. Only the HMAC of the token reaches the
 * database, so a read of this table hands out nothing.
 *
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property string $token the HMAC, never the token itself
 * @property DateTime|null $expires_at
 * @property DateTime $created_at
 *
 * @property-read User $user {@see static::getUser()}
 */
class UserToken extends ActiveRecord
{
    public const string TYPE_VERIFICATION = 'verification';
    public const string TYPE_PASSWORD_RESET = 'password';
    public const string TYPE_RECOVERY_CODE = 'recovery';

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['user_id'],
                RelationValidator::class,
                'required' => true,
            ],
            [
                ['type', 'token'],
                'required',
            ],
        ];
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * @return UserTokenQuery<static>
     */
    #[Override]
    public static function find(): UserTokenQuery
    {
        return Yii::createObject(UserTokenQuery::class, [static::class]);
    }

    /**
     * @param int|null $lifetime seconds until the token expires, `null` for one that is spent rather than aged out
     */
    public static function issue(int $userId, string $type, string $token, ?int $lifetime = null): static
    {
        $record = static::create();
        $record->user_id = $userId;
        $record->type = $type;
        $record->token = static::hash($token);
        $record->expires_at = $lifetime === null ? null : new DateTime("+$lifetime seconds");

        $record->insert();

        return $record;
    }

    /**
     * @return UserQuery<User>
     */
    public function getUser(): UserQuery
    {
        /** @var UserQuery<User> $query */
        $query = $this->hasOne(User::class, ['id' => 'user_id']);
        return $query;
    }

    /**
     * The token is only ever compared as its HMAC, which is both constant time and keyed — a leaked table cannot
     * be turned back into working links even for the short tokens.
     */
    public static function hash(string $token): string
    {
        return hash_hmac('sha256', trim($token), SecretKey::get());
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%user_token}}';
    }
}
