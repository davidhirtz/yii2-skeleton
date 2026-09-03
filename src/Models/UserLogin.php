<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\I18n\Lang;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Validators\RelationValidator;
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

    public function getTypeIcon(): string
    {
        return $this->getTypeOptions()['icon'] ?: "brand:$this->type";
    }

    public static function getTypes(): array
    {
        return [
            static::TYPE_LOGIN => [
                'name' => Lang::t('skeleton', 'COMMON_LOGIN'),
                'icon' => 'sign-in-alt',
            ],
            static::TYPE_COOKIE => [
                'name' => Lang::t('skeleton', 'USER_LOGIN_COOKIE'),
                'icon' => 'heart',
            ],
            static::TYPE_SIGNUP => [
                'name' => Lang::t('skeleton', 'USER_LOGIN_SIGN_UP'),
                'icon' => 'user-plus',
            ],
            static::TYPE_CONFIRM_EMAIL => [
                'name' => Lang::t('skeleton', 'USER_LOGIN_EMAIL_CONFIRMATION'),
                'icon' => 'envelope',
            ],
            static::TYPE_RESET_PASSWORD => [
                'name' => Lang::t('skeleton', 'USER_LOGIN_PASSWORD_RESET'),
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
            'typeName' => Lang::t('skeleton', 'USER_LOGIN_TYPENAME_LABEL'),
            'browser' => Lang::t('skeleton', 'USER_LOGIN_BROWSER_LABEL'),
            'ip_address' => Lang::t('skeleton', 'USER_LOGIN_IP_ADDRESS_LABEL'),
            'user' => Lang::t('skeleton', 'USER_LOGIN_USER_LABEL'),
            'created_at' => Lang::t('skeleton', 'USER_LOGIN_CREATED_AT_LABEL'),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%user_login}}';
    }
}
