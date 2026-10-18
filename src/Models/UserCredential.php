<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Helpers\SecretKey;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Validators\RelationValidator;
use Override;
use Yii;

/**
 * A user's own key for a third-party service. Written once and never shown again: only {@see static::findSecret()}
 * decrypts it, for the code that calls the service.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $secret the encrypted key, never the key itself
 * @property string $hint
 * @property DateTime|null $last_used_at
 * @property DateTime $created_at
 *
 * @property-read User $user {@see static::getUser()}
 */
class UserCredential extends ActiveRecord
{
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
                ['provider', 'secret', 'hint'],
                'required',
            ],
            [
                ['provider'],
                'string',
                'max' => 32,
            ],
        ];
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'provider' => Yii::t('skeleton', 'CREDENTIAL_PROVIDER_LABEL'),
            'hint' => Yii::t('skeleton', 'CREDENTIAL_SECRET_LABEL'),
            'last_used_at' => Yii::t('skeleton', 'CREDENTIAL_LAST_USED_AT_LABEL'),
            'created_at' => Yii::t('skeleton', 'CREDENTIAL_CREATED_AT_LABEL'),
        ];
    }

    /**
     * Saving a key for a service the user already has one for replaces it.
     */
    public static function store(int $userId, string $provider, string $secret): static
    {
        $record = static::findOne(['user_id' => $userId, 'provider' => $provider]) ?? static::create();
        $record->user_id = $userId;
        $record->provider = $provider;
        $record->setSecret($secret);
        $record->save();

        return $record;
    }

    public static function findSecret(int $userId, string $provider): ?string
    {
        $record = static::findOne(['user_id' => $userId, 'provider' => $provider]);
        $secret = $record ? SecretKey::decrypt($record->secret) : null;

        if ($record && $secret !== null && ($record->last_used_at?->getTimestamp() ?? 0) < time() - 60) {
            $record->updateAttributes(['last_used_at' => new DateTime()]);
        }

        return $secret;
    }

    public function setSecret(string $secret): void
    {
        $secret = trim($secret);

        $this->secret = SecretKey::encrypt($secret);
        $this->hint = '…' . mb_substr($secret, -4);
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

    #[Override]
    public static function tableName(): string
    {
        return '{{%user_credential}}';
    }
}
