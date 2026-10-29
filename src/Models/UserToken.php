<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\SecretKey;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Queries\UserTokenQuery;
use Hirtz\Skeleton\Validators\RelationValidator;
use Override;
use Yii;

/**
 * @property string $id
 * @property int $user_id
 * @property int|null $grant_id
 * @property string $type
 * @property string $token
 * @property DateTime|null $expires_at
 * @property DateTime|null $last_used_at
 * @property DateTime $created_at
 *
 * @property-read User $user
 * @property-read OAuthGrant|null $grant
 */
class UserToken extends ActiveRecord
{
    public const string TYPE_VERIFICATION = 'verification';
    public const string TYPE_PASSWORD_RESET = 'password';
    public const string TYPE_RECOVERY_CODE = 'recovery';
    public const string TYPE_MCP_READ = 'mcp-read';
    public const string TYPE_MCP_WRITE = 'mcp-write';
    public const string TYPE_MCP_REFRESH = 'mcp-refresh';

    /**
     * A refresh token already exchanged, kept until it expires so that its reuse is noticed.
     */
    public const string TYPE_MCP_ROTATED = 'mcp-rotated';

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
     * A `null` lifetime is a token that is spent rather than aged out.
     */
    public static function issue(int $userId, string $type, string $token, ?int $lifetime = null, ?int $grantId = null): static
    {
        $record = static::create();
        $record->user_id = $userId;
        $record->grant_id = $grantId;
        $record->type = $type;
        $record->token = static::hash($token);
        $record->expires_at = $lifetime === null ? null : new DateTime("+$lifetime seconds");

        $record->insert();

        return $record;
    }

    /**
     * At most once per interval: an MCP token authenticates every request.
     */
    public function touchLastUsedAt(int $interval = 60): void
    {
        if ($this->last_used_at === null || $this->last_used_at->getTimestamp() < time() - $interval) {
            $this->updateAttributes(['last_used_at' => new DateTime()]);
        }
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
     * @return ActiveQuery<OAuthGrant>
     */
    public function getGrant(): ActiveQuery
    {
        /** @var ActiveQuery<OAuthGrant> $query */
        $query = $this->hasOne(OAuthGrant::class, ['id' => 'grant_id']);
        return $query;
    }

    /**
     * Only ever stored and compared as a keyed HMAC, so a leaked table cannot be turned back into working links.
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
