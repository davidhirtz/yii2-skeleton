<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use DateTimeZone;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\AdminRouteInterface;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Validators\UniqueValidator;
use Override;
use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property int $status
 * @property string|null $name
 * @property string $email
 * @property string|null $password_hash
 * @property string|null $password_salt
 * @property string $language
 * @property string|null $timezone
 * @property string|null $auth_key
 * @property string|null $verification_token
 * @property string|null $password_reset_token
 * @property string|null $google_2fa_secret
 * @property bool|int $is_owner
 * @property int $created_by_user_id
 * @property int $login_count
 * @property DateTime|null $last_login
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 * @property array|null $custom_attributes
 *
 * @property-read User|null $created {@see static::getCreated()}
 *
 * @mixin TrailBehavior
 */
class User extends ActiveRecord implements AdminRouteInterface, CustomAttributeInterface, IdentityInterface, StatusAttributeInterface, TrailModelInterface
{
    use CustomAttributesTrait;
    use StatusAttributeTrait;
    use TrailModelTrait;

    final public const string AUTH_USER_CREATE = 'userCreate';
    final public const string AUTH_USER_DELETE = 'userDelete';
    final public const string AUTH_USER_UPDATE = 'userUpdate';
    final public const string AUTH_USER_ASSIGN = 'authUpdate';
    final public const string AUTH_ROLE_ADMIN = 'admin';

    /**
     * @var int the minimum length for the username
     */
    public int $nameMinLength = 3;

    /**
     * @var int the maximum length for the username
     */
    public int $nameMaxLength = 32;

    /**
     * @var string|false the pattern for the username, set false to disable pattern validation
     */
    public string|false $namePattern = '/^\d*[a-z][a-z0-9\.-]*[a-z0-9]$/si';

    /**
     * @var int the minimum length for the password
     */
    public int $passwordMinLength = 5;

    /**
     * @var bool whether the name is required
     */
    public bool $requireName = true;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TimestampBehavior' => TimestampBehavior::class,
            'TrailBehavior' => [
                'class' => TrailBehavior::class,
                'modelClass' => User::class,
            ],
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['name', 'email'],
                'trim',
            ],
            [
                ['email'],
                'required',
            ],
            [
                ['language', 'timezone'],
                DynamicRangeValidator::class,
                'integerOnly' => false,
            ],
            [
                ['name'],
                $this->requireName ? 'required' : 'safe',
            ],
            [
                ['name'],
                'string',
                'min' => $this->nameMinLength,
                'max' => max($this->nameMinLength, $this->nameMaxLength),
                'skipOnError' => true,
            ],
            [
                ['name'],
                'match',
                'pattern' => $this->namePattern,
                'message' => Yii::t('skeleton', 'USER_USERNAME_MUST_ONLY'),
                'skipOnError' => true,
                'when' => fn () => $this->namePattern !== false,
            ],
            [
                ['name'],
                UniqueValidator::class,
                'message' => Yii::t('skeleton', 'USER_USERNAME_ALREADY_USED'),
            ],
            [
                ['email'],
                'string',
                'max' => 100,
            ],
            [
                ['email'],
                'email',
                'skipOnError' => true,
            ],
            [
                ['email'],
                'unique',
                'message' => Yii::t('skeleton', 'USER_EMAIL_ADDRESS_ALREADY'),
                'skipOnError' => true,
                'when' => fn () => $this->isAttributeChanged('email')
            ],
        ];
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return $this->password_hash && Yii::$app->getSecurity()->validatePassword($password . $this->password_salt, $this->password_hash);
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->generateAuthKey();
        }

        return parent::beforeSave($insert);
    }

    #[Override]
    public function delete(): false|int
    {
        if (!$this->isDeletable()) {
            $this->addError('id', $this->isOwner()
                ? Yii::t('skeleton', 'USER_USER_WEBSITE_OWNER')
                : Yii::t('skeleton', 'USER_THE_USER_CANNOT_BE_DELETED'));

            return false;
        }

        return parent::delete();
    }

    /**
     * @return UserQuery<static>
     */
    public function getCreated(): UserQuery
    {
        /** @var UserQuery $query */
        $query = $this->hasOne(static::class, ['id' => 'created_by_user_id']);
        return $query;
    }

    /**
     * @return UserQuery<static>
     */
    #[Override]
    public static function find(): UserQuery
    {
        return Yii::createObject(UserQuery::class, [static::class]);
    }

    public static function findIdentity($id): ?static
    {
        /** @var static|null $identity */
        $identity = static::find()
            ->where(['id' => $id])
            ->enabled()
            ->one();

        if ($identity?->timezone) {
            Yii::$app->setTimeZone($identity->timezone);
        }

        return $identity;
    }

    public static function findIdentityByAccessToken($token, $type = null): ?static
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public function afterPasswordChange(): void
    {
        $trail = Trail::create();
        $trail->model_class = User::class;
        $trail->model_id = (string)$this->id;
        $trail->type = Trail::TYPE_PASSWORD;
        $trail->insert();
    }

    public function generatePasswordHash(string $password): void
    {
        $this->password_salt = Yii::$app->getSecurity()->generateRandomString(10);
        $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password . $this->password_salt);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->getSecurity()->generateRandomString();
    }

    public function generateVerificationToken(): void
    {
        $this->verification_token = Yii::$app->getSecurity()->generateRandomString();
    }

    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = Yii::$app->getSecurity()->generateRandomString();
    }

    public function getAdminRoute(): array
    {
        return $this->id ? ['/admin/user/update', 'id' => $this->id] : ['/admin/user/index'];
    }

    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }

    public function getId(): mixed
    {
        return $this->getPrimaryKey();
    }

    public function getInitials(): string
    {
        return substr((string)$this->name, 0, 2);
    }

    public function getEmailConfirmationUrl(): ?string
    {
        if (!$this->verification_token) {
            return null;
        }

        /** @see AccountController::actionConfirm() */
        return Yii::$app->getUrlManager()->createAbsoluteUrl([
            '/admin/account/confirm',
            'email' => $this->email,
            'code' => $this->verification_token,
        ]);
    }

    public function getPasswordResetUrl(): ?string
    {
        if (!$this->password_reset_token) {
            return null;
        }

        /** @see AccountController::actionReset() */
        return Yii::$app->getUrlManager()->createAbsoluteUrl([
            '/admin/account/reset',
            'email' => $this->email,
            'code' => $this->password_reset_token,
        ]);
    }

    public function getTimezoneOffset(): string
    {
        $date = new \DateTime('now', new DateTimeZone($this->timezone ?? Yii::$app->getTimeZone()));
        return 'GMT ' . $date->format('P');
    }

    public function getUsername(): ?string
    {
        return $this->getOldAttributes()['name'] ?? $this->name;
    }

    public static function getStatuses(): array
    {
        return [
            static::STATUS_DISABLED => [
                'name' => Yii::t('skeleton', 'COMMON_DISABLED'),
                'icon' => 'exclamation-triangle',
            ],
            static::STATUS_ENABLED => [
                'name' => Yii::t('skeleton', 'COMMON_ENABLED'),
                'icon' => 'user',
            ],
        ];
    }

    public function getStatusName(): string
    {
        if ($this->isOwner()) {
            return Yii::t('skeleton', 'USER_SITE_OWNER');
        }

        return static::getStatuses()[$this->status]['name'] ?? '';
    }

    public function getStatusIcon(): string
    {
        return !$this->isOwner() ? (static::getStatuses()[$this->status]['icon'] ?? '') : 'star';
    }

    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            $this->getCustomAttributesColumn(),
            'password_hash',
            'password_salt',
            'auth_key',
            'verification_token',
            'password_reset_token',
            'google_2fa_secret',
            'login_count',
            'last_login',
            'created_by_user_id',
            'updated_at',
            'created_at',
        ]);
    }

    public function getTrailModelName(): string
    {
        return $this->id ? $this->getUsername() : $this->getTrailModelType();
    }

    public function getTrailModelType(): string
    {
        return Yii::t('skeleton', 'COMMON_USER');
    }

    public function isDeletable(): bool
    {
        return !$this->isOwner();
    }

    public function isOwner(): bool
    {
        return (bool)$this->is_owner;
    }

    public function isUnconfirmed(): bool
    {
        return !empty($this->verification_token);
    }

    /**
     * @noinspection PhpUnused
     */
    public static function getLanguages(): array
    {
        $i18n = Yii::$app->getI18n();
        $languages = [];

        foreach (Yii::$app->getI18n()->getLanguages() as $language) {
            $languages[$language]['name'] = $i18n->getLabel($language);
        }

        return $languages;
    }

    /**
     * @noinspection PhpUnused
     */
    public static function getTimezones(): array
    {
        return array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers());
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'id' => Yii::t('skeleton', 'USER_ID_LABEL'),
            'name' => Yii::t('skeleton', 'USER_NAME_LABEL'),
            'email' => Yii::t('skeleton', 'USER_EMAIL_LABEL'),
            'password' => Yii::t('skeleton', 'USER_PASSWORD_LABEL'),
            'language' => Yii::t('skeleton', 'USER_LANGUAGE_LABEL'),
            'timezone' => Yii::t('skeleton', 'USER_TIMEZONE_LABEL'),
            'verification_token' => Yii::t('skeleton', 'USER_VERIFICATION_TOKEN_LABEL'),
            'login_count' => Yii::t('skeleton', 'USER_LOGIN_COUNT_LABEL'),
            'last_login' => Yii::t('skeleton', 'USER_LAST_LOGIN_LABEL'),
            'is_owner' => Yii::t('skeleton', 'USER_IS_OWNER_LABEL'),
            'updated_at' => Yii::t('skeleton', 'USER_UPDATED_AT_LABEL'),
            'created_at' => Yii::t('skeleton', 'USER_CREATED_AT_LABEL'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'User';
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%user}}';
    }
}
