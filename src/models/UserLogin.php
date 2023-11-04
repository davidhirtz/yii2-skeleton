<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property string $browser
 * @property int $ip_address
 * @property DateTime $created_at
 *
 * @property User $user {@see \davidhirtz\yii2\skeleton\models\UserLogin::getUser()}
 * @property-read string $typeName
 * @property-read string $displayIp
 */
class UserLogin extends ActiveRecord
{
    /**
     * Type codes.
     */
    public const TYPE_COOKIE = 'auto';
    public const TYPE_LOGIN = 'login';
    public const TYPE_SIGNUP = 'signup';
    public const TYPE_CONFIRM_EMAIL = 'email';
    public const TYPE_RESET_PASSWORD = 'password';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'DateTimeBehavior' => DateTimeBehavior::class,
        ];
    }

    public function getUser(): UserQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return string
     */
    public function getTypeName()
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

    /**
     * @return string
     */
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

    /**
     * @return string
     */
    public function getDisplayIp(): string
    {
        return inet_ntop($this->ip_address) ?: '';
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_login}}';
    }
}
