<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\traits\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\validators\RelationValidator;
use Override;
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
class UserLogin extends ActiveRecord implements TypeAttributeInterface
{
    use TypeAttributeTrait;

    public const string TYPE_COOKIE = 'auto';
    public const string TYPE_LOGIN = 'login';
    public const string TYPE_SIGNUP = 'signup';
    public const string TYPE_CONFIRM_EMAIL = 'email';
    public const string TYPE_RESET_PASSWORD = 'password';

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['user_id'],
                RelationValidator::class,
                'required' => true,
            ],
            [
                ['type'],
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
        ];
    }

    /**
     * @return UserQuery<User>
     */
    public function getUser(): UserQuery
    {
        /** @var UserQuery $query */
        $query = $this->hasOne(User::class, ['id' => 'user_id']);
        return $query;
    }

    public function getTypeName(): string
    {
        return $this->getTypeOptions()['name'] ?? ucfirst($this->type);
    }

    public static function getTypes(): array
    {
        return [
            static::TYPE_LOGIN => [
                'name' => Yii::t('skeleton', 'Login'),
                'icon' => 'sign-in-alt',
            ],
            static::TYPE_COOKIE => [
                'name' => Yii::t('skeleton', 'Cookie'),
                'icon' => 'heart',
            ],
            static::TYPE_SIGNUP => [
                'name' => Yii::t('skeleton', 'Sign up'),
                'icon' => 'user-plus',
            ],
            static::TYPE_CONFIRM_EMAIL => [
                'name' => Yii::t('skeleton', 'Email confirmation'),
                'icon' => 'envelope',
            ],
            static::TYPE_RESET_PASSWORD => [
                'name' => Yii::t('skeleton', 'Password reset'),
                'icon' => 'unlock',
            ],
        ];
    }

    public function getDisplayIp(): string
    {
        return $this->ip_address ? (inet_ntop($this->ip_address) ?: '-') : '';
    }

    #[Override]
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

    #[Override]
    public static function tableName(): string
    {
        return '{{%user_login}}';
    }
}
