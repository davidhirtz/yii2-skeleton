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
 * A user's own key for a third-party service, one or several values as the service's fields name them. Written once
 * and never shown again: only {@see static::findValues()} decrypts it, for the code that calls the service.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $secret the encrypted values, never the values themselves
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
     *
     * @param array<string, string> $values
     */
    public static function store(int $userId, string $provider, array $values): static
    {
        $record = static::findOne(['user_id' => $userId, 'provider' => $provider]) ?? static::create();
        $record->user_id = $userId;
        $record->provider = $provider;
        $record->setValues($values);
        $record->save();

        return $record;
    }

    /**
     * @return array<string, string>|null
     */
    public static function findValues(int $userId, string $provider): ?array
    {
        $record = static::findOne(['user_id' => $userId, 'provider' => $provider]);
        $values = $record ? $record->getValues() : null;

        if ($record && $values !== null && ($record->last_used_at?->getTimestamp() ?? 0) < time() - 60) {
            $record->updateAttributes(['last_used_at' => new DateTime()]);
        }

        return $values;
    }

    /**
     * The hint is the end of the last value, the secret where a service asks for an ID and a secret.
     *
     * @param array<string, string> $values
     */
    public function setValues(array $values): void
    {
        $values = array_map(trim(...), $values);

        $this->secret = SecretKey::encrypt(json_encode($values, JSON_THROW_ON_ERROR));
        $this->hint = '…' . mb_substr((string)end($values), -4);
    }

    /**
     * @return array<string, string>|null
     */
    protected function getValues(): ?array
    {
        $json = SecretKey::decrypt($this->secret);
        $values = $json !== null ? json_decode($json, true) : null;

        return is_array($values) ? array_map(strval(...), array_filter($values, is_scalar(...))) : null;
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
