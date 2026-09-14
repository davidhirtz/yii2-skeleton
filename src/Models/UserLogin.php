<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Migrations\M260914180000UserLoginType;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Validators\RelationValidator;
use Override;
use Yii;

/**
 * @property string $id
 * @property int $user_id
 * @property int $type
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

    /**
     * Deliberately {@see TypeAttributeInterface::TYPE_DEFAULT}: a login whose caller named no type has told us
     * nothing, which is what "other" means. It is also where {@see M260914180000UserLoginType} collects the
     * provider names the removed social login wrote.
     */
    public const int TYPE_OTHER = self::TYPE_DEFAULT;

    public const int TYPE_LOGIN = 2;
    public const int TYPE_COOKIE = 3;
    public const int TYPE_SIGNUP = 4;
    public const int TYPE_CONFIRM_EMAIL = 5;
    public const int TYPE_RESET_PASSWORD = 6;

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

    /**
     * @return list<Type>
     */
    public function getTypes(): array
    {
        return [
            Type::make(static::TYPE_OTHER)
                ->name(Yii::t('skeleton', 'USER_LOGIN_OTHER'))
                ->icon('question'),
            Type::make(static::TYPE_LOGIN)
                ->name(Yii::t('skeleton', 'COMMON_LOGIN'))
                ->icon('sign-in-alt'),
            Type::make(static::TYPE_COOKIE)
                ->name(Yii::t('skeleton', 'USER_LOGIN_COOKIE'))
                ->icon('heart'),
            Type::make(static::TYPE_SIGNUP)
                ->name(Yii::t('skeleton', 'USER_LOGIN_SIGN_UP'))
                ->icon('user-plus'),
            Type::make(static::TYPE_CONFIRM_EMAIL)
                ->name(Yii::t('skeleton', 'USER_LOGIN_EMAIL_CONFIRMATION'))
                ->icon('envelope'),
            Type::make(static::TYPE_RESET_PASSWORD)
                ->name(Yii::t('skeleton', 'USER_LOGIN_PASSWORD_RESET'))
                ->icon('unlock'),
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
            'typeName' => Yii::t('skeleton', 'USER_LOGIN_TYPENAME_LABEL'),
            'browser' => Yii::t('skeleton', 'USER_LOGIN_BROWSER_LABEL'),
            'ip_address' => Yii::t('skeleton', 'USER_LOGIN_IP_ADDRESS_LABEL'),
            'user' => Yii::t('skeleton', 'USER_LOGIN_USER_LABEL'),
            'created_at' => Yii::t('skeleton', 'USER_LOGIN_CREATED_AT_LABEL'),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%user_login}}';
    }
}
