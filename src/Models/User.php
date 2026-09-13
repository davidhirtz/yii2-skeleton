<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use DateTimeZone;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Validators\UniqueValidator;
use Override;
use Yii;
use yii\base\InvalidConfigException;
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
 * @property DateTime|null $verification_token_created_at
 * @property string|null $password_reset_token
 * @property DateTime|null $password_reset_token_created_at
 * @property string|null $google_2fa_secret the encrypted secret, reached through
 *     {@see static::getTwoFactorAuthenticationSecret()}
 * @property array|null $google_2fa_recovery_codes the hashed single-use codes
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
class User extends ActiveRecord implements CustomAttributeInterface, IdentityInterface, SearchableInterface, StatusAttributeInterface, TrailModelInterface
{
    use AdminModelTrait;
    use CustomAttributesTrait;
    use SearchableTrait;
    use StatusAttributeTrait;
    use TrailModelTrait;

    final public const string AUTH_USER_CREATE = 'userCreate';
    final public const string AUTH_USER_DELETE = 'userDelete';
    final public const string AUTH_USER_UPDATE = 'userUpdate';
    final public const string AUTH_USER_ASSIGN = 'authUpdate';
    final public const string AUTH_ROLE_ADMIN = 'admin';

    /**
     * Marks a secret written by {@see static::encryptTwoFactorAuthenticationSecret()}, so a row that predates the
     * encryption is still readable.
     */
    private const string ENCRYPTED_PREFIX = 'enc:';

    private const string RECOVERY_CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    final public const int RECOVERY_CODE_LENGTH = 10;

    final public const int RECOVERY_CODE_COUNT = 8;

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
    public int $passwordMinLength = 8;

    /**
     * @var int the maximum length for the password. bcrypt silently truncates at 72 bytes, so anything past it is
     * not part of the password and must not be accepted as if it were.
     */
    public int $passwordMaxLength = 72;

    /**
     * @var bool whether the name is required
     */
    public bool $requireName = true;

    /**
     * @var int how long a verification or password reset token stays valid, in seconds. A token also lives in the
     * user's inbox and in every mail archive along the way, so it must not be usable forever.
     */
    public int $tokenLifetime = 86400;

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
        return (bool)$this->password_hash
            && Yii::$app->getSecurity()->validatePassword($this->getSeasonedPassword($password), $this->password_hash);
    }

    /**
     * True while the stored hash was written with the legacy `password_salt` or below the security component's
     * current cost, so a successful login can replace it.
     */
    public function isPasswordHashOutdated(): bool
    {
        if (!$this->password_hash) {
            return false;
        }

        return (bool)$this->password_salt || password_needs_rehash($this->password_hash, PASSWORD_BCRYPT, [
            'cost' => Yii::$app->getSecurity()->passwordHashCost,
        ]);
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
        // bcrypt carries a salt of its own, so the column adds nothing — it is only kept for the hashes that
        // were written with it. Clearing it first is what makes the pepper apply below.
        $this->password_salt = null;
        $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($this->getSeasonedPassword($password));
    }

    /**
     * The optional `passwordPepper` param is a secret the database does not hold, so a leaked hash cannot be
     * attacked offline. A hash written before it keeps its own `password_salt` and is still checked with that.
     */
    private function getSeasonedPassword(string $password): string
    {
        return $password . ($this->password_salt ?? (Yii::$app->params['passwordPepper'] ?? ''));
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->getSecurity()->generateRandomString();
    }

    public function generateVerificationToken(): void
    {
        $this->verification_token = Yii::$app->getSecurity()->generateRandomString();
        $this->verification_token_created_at = new DateTime();
    }

    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = Yii::$app->getSecurity()->generateRandomString();
        $this->password_reset_token_created_at = new DateTime();
    }

    public function clearVerificationToken(): void
    {
        $this->verification_token = null;
        $this->verification_token_created_at = null;
    }

    public function clearPasswordResetToken(): void
    {
        $this->password_reset_token = null;
        $this->password_reset_token_created_at = null;
    }

    public function isVerificationTokenValid(?string $token): bool
    {
        return $this->isTokenValid($this->verification_token, $this->verification_token_created_at, $token);
    }

    public function isPasswordResetTokenValid(?string $token): bool
    {
        return $this->isTokenValid($this->password_reset_token, $this->password_reset_token_created_at, $token);
    }

    private function isTokenValid(?string $expected, ?DateTime $createdAt, ?string $token): bool
    {
        if (!$expected || !$token || !$createdAt) {
            return false;
        }

        if ($createdAt->getTimestamp() + $this->tokenLifetime < time()) {
            return false;
        }

        return Yii::$app->getSecurity()->compareString($expected, $token);
    }

    public function hasTwoFactorAuthentication(): bool
    {
        return (bool)$this->google_2fa_secret;
    }

    public function getTwoFactorAuthenticationSecret(): ?string
    {
        return $this->google_2fa_secret === null
            ? null
            : static::decryptTwoFactorAuthenticationSecret($this->google_2fa_secret);
    }

    public function setTwoFactorAuthenticationSecret(?string $secret): void
    {
        $this->google_2fa_secret = $secret === null
            ? null
            : static::encryptTwoFactorAuthenticationSecret($secret);

        $this->google_2fa_recovery_codes = null;
    }

    /**
     * @return list<string> the codes in the clear, which is the only time they can be shown — only their hashes
     *     are kept
     */
    public function generateTwoFactorAuthenticationRecoveryCodes(): array
    {
        $codes = [];
        $hashes = [];

        for ($i = 0; $i < static::RECOVERY_CODE_COUNT; $i++) {
            $code = '';

            for ($j = 0; $j < static::RECOVERY_CODE_LENGTH; $j++) {
                $code .= self::RECOVERY_CODE_ALPHABET[random_int(0, strlen(self::RECOVERY_CODE_ALPHABET) - 1)];
            }

            $codes[] = $code;
            $hashes[] = self::hashTwoFactorAuthenticationRecoveryCode($code);
        }

        $this->google_2fa_recovery_codes = $hashes;

        return $codes;
    }

    /**
     * Consumes the code it matches: a recovery code is good once, so this writes the shortened list before it
     * returns.
     */
    public function validateTwoFactorAuthenticationRecoveryCode(string $code): bool
    {
        $hash = self::hashTwoFactorAuthenticationRecoveryCode($code);
        $remaining = [];
        $matched = false;

        foreach ($this->google_2fa_recovery_codes ?? [] as $stored) {
            if (!$matched && hash_equals((string)$stored, $hash)) {
                $matched = true;
                continue;
            }

            $remaining[] = $stored;
        }

        if ($matched) {
            $this->google_2fa_recovery_codes = $remaining;
            $this->updateAttributes(['google_2fa_recovery_codes' => $remaining]);
        }

        return $matched;
    }

    public function getTwoFactorAuthenticationRecoveryCodeCount(): int
    {
        return count($this->google_2fa_recovery_codes ?? []);
    }

    public static function encryptTwoFactorAuthenticationSecret(string $secret): string
    {
        $data = Yii::$app->getSecurity()->encryptByKey($secret, self::getTwoFactorAuthenticationKey());
        return self::ENCRYPTED_PREFIX . base64_encode($data);
    }

    public static function decryptTwoFactorAuthenticationSecret(string $secret): ?string
    {
        if (!str_starts_with($secret, self::ENCRYPTED_PREFIX)) {
            // Written before the column was encrypted, and still the secret itself
            return $secret;
        }

        $data = base64_decode(substr($secret, strlen(self::ENCRYPTED_PREFIX)), true);
        $secret = $data === false ? false : Yii::$app->getSecurity()->decryptByKey($data, self::getTwoFactorAuthenticationKey());

        return $secret === false ? null : $secret;
    }

    private static function hashTwoFactorAuthenticationRecoveryCode(string $code): string
    {
        // A recovery code is 50 bits of entropy the user never chose, so it needs no key stretching
        return hash_hmac('sha256', strtoupper(trim($code)), self::getTwoFactorAuthenticationKey());
    }

    /**
     * @throws InvalidConfigException
     */
    private static function getTwoFactorAuthenticationKey(): string
    {
        $key = Yii::$app->params['secretKey'] ?? Yii::$app->params['cookieValidationKey'] ?? null;

        if (!$key) {
            throw new InvalidConfigException('Either `secretKey` or `cookieValidationKey` must be set in params.');
        }

        return $key;
    }

    public function getAdminRoute(): array
    {
        return $this->id ? ['/admin/user/update', 'id' => $this->id] : ['/admin/user/index'];
    }

    public function getSearchAttributes(): array
    {
        return ['name', 'email'];
    }

    public function getSearchWeight(): float
    {
        return 0.5;
    }

    protected function isSearchResultVisible(): bool
    {
        return Yii::$app->has('user') && Yii::$app->getUser()->can(static::AUTH_USER_UPDATE, ['user' => $this]);
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
            'verification_token_created_at',
            'password_reset_token',
            'password_reset_token_created_at',
            'google_2fa_secret',
            'google_2fa_recovery_codes',
            'login_count',
            'last_login',
            'created_by_user_id',
            'updated_at',
            'created_at',
        ]);
    }

    public function getAdminName(): string
    {
        return $this->id ? $this->getUsername() : $this->getAdminType();
    }

    public function getAdminType(): string
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
