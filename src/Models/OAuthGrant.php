<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Validators\RelationValidator;
use Override;
use Yii;

/**
 * @property int $id
 * @property int $user_id
 * @property string $client_id
 * @property string|null $client_name
 * @property string $resource
 * @property string $scope
 * @property DateTime|null $last_used_at
 * @property DateTime $created_at
 *
 * @property-read User $user
 */
class OAuthGrant extends ActiveRecord
{
    final public const string SCOPE_READ = 'mcp:read';
    final public const string SCOPE_WRITE = 'mcp:write';

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
                ['client_id', 'resource', 'scope'],
                'required',
            ],
            [
                ['client_id', 'resource'],
                'string',
                'max' => 255,
            ],
            [
                ['client_name'],
                'string',
                'max' => 100,
            ],
            [
                ['scope'],
                'in',
                'range' => [self::SCOPE_READ, self::SCOPE_WRITE],
            ],
        ];
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'client_name' => Yii::t('skeleton', 'GRANT_CLIENT_NAME_LABEL'),
            'scope' => Yii::t('skeleton', 'GRANT_SCOPE_LABEL'),
            'last_used_at' => Yii::t('skeleton', 'GRANT_LAST_USED_AT_LABEL'),
            'created_at' => Yii::t('skeleton', 'GRANT_CREATED_AT_LABEL'),
        ];
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

    public function isReadOnly(): bool
    {
        return $this->scope !== self::SCOPE_WRITE;
    }

    /**
     * At most once per interval: it is written on every authenticated request.
     */
    public function touchLastUsedAt(int $interval = 60): void
    {
        if ($this->last_used_at === null || $this->last_used_at->getTimestamp() < time() - $interval) {
            $this->updateAttributes(['last_used_at' => new DateTime()]);
        }
    }

    public function getClientHost(): string
    {
        return (string)parse_url($this->client_id, PHP_URL_HOST);
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%oauth_grant}}';
    }
}
