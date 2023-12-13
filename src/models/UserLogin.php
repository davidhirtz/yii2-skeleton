<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use Yii;

/**
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property string|null $browser
 * @property string|null $ip_address
 * @property DateTime $created_at
 *
 * @property User $user {@see UserLogin::getUser}
 * @property-read string $typeName
 * @property-read string $displayIp
 */
class UserLogin extends ActiveRecord
{
    public const TYPE_COOKIE = 'auto';
    public const TYPE_LOGIN = 'login';
    public const TYPE_SIGNUP = 'signup';
    public const TYPE_CONFIRM_EMAIL = 'email';
    public const TYPE_RESET_PASSWORD = 'password';

    public function behaviors(): array
    {
        return [
            'DateTimeBehavior' => DateTimeBehavior::class,
        ];
    }

    public function getUser(): UserQuery
    {
        /** @var UserQuery $query */
        $query = $this->hasOne(User::class, ['id' => 'user_id']);
        return $query;
    }

    public function getTypeName(): string
    {
        return match ($this->type) {
            static::TYPE_LOGIN => Yii::t('skeleton', 'Login'),
            static::TYPE_COOKIE => Yii::t('skeleton', 'Cookie'),
            static::TYPE_SIGNUP => Yii::t('skeleton', 'Sign up'),
            static::TYPE_CONFIRM_EMAIL => Yii::t('skeleton', 'Email confirmation'),
            static::TYPE_RESET_PASSWORD => Yii::t('skeleton', 'Password reset'),
            default => ucfirst($this->type),
        };
    }

    public function getTypeIcon(): ?string
    {
        return match ($this->type) {
            static::TYPE_LOGIN => 'sign-in-alt',
            static::TYPE_COOKIE => 'heart',
            static::TYPE_SIGNUP => 'user-plus',
            static::TYPE_CONFIRM_EMAIL => 'envelope',
            static::TYPE_RESET_PASSWORD => 'unlock',
            default => null,
        };
    }

    public function getDisplayIp(): string
    {
        return $this->ip_address ?? inet_ntop($this->ip_address) ?: '';
    }

    public function attributeLabels(): array
    {
        return [
            'typeName' => Yii::t('skeleton', 'Login'),
            'browser' => Yii::t('skeleton', 'Browser'),
            'ip_address' => Yii::t('skeleton', 'IP'),
            'user' => Yii::t('skeleton', 'User'),
            'created_at' => Yii::t('skeleton', 'Login'),
        ];
    }

    public static function tableName(): string
    {
        return '{{%user_login}}';
    }
}
