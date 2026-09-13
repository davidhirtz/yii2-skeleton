<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use DateTimeZone;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\SecretKey;
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
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property int $status
 * @property string|null $name
 * @property string $email
 * @property DateTime|null $email_confirmed_at
 * @property string|null $password_hash
 * @property string|null $password_scheme which scheme the hash was written with —
 *     {@see static::PASSWORD_PEPPER} or `null`
 * @property string $language
 * @property string|null $timezone
 * @property string|null $auth_key
 * @property string|null $two_factor_secret the encrypted secret, reached through
 *     {@see static::getTwoFactorAuthenticationSecret()}
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

    /**
     * The role markers of {@see \yii\filters\AccessRule}, which a permission name can be mixed with wherever
     * roles are declared — an `AccessRule` and {@see \Hirtz\Skeleton\Widgets\Traits\VisibilityTrait} alike.
     */
    final public const string ROLE_ANY = '*';
    final public const string ROLE_AUTHENTICATED = '@';

    final public const string AUTH_USER = 'user';
    final public const string AUTH_USER_ASSIGN = 'authUpdate';
    final public const string AUTH_ROLE_ADMIN = 'admin';

    /**
     * Recorded in `password_scheme` for a hash written with the `passwordPepper` param, which is what lets a
     * pepper be added, removed or rotated on a running installation: a hash whose scheme disagrees with the
     * configured pepper is outdated, not broken, and the next successful login rewrites it.
     */
    final public const string PASSWORD_PEPPER = 'pepper';

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
     * True while the stored hash was written under a scheme the application no longer uses — a legacy per-user
     * salt, a pepper that has since been added or removed, or a cost below the security component's current one.
     * A successful login is where it gets rewritten.
     */
    public function isPasswordHashOutdated(): bool
    {
        if (!$this->password_hash) {
            return false;
        }

        return $this->password_scheme !== self::getPasswordSchemeForNewHash()
            || password_needs_rehash($this->password_hash, PASSWORD_BCRYPT, [
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
        // bcrypt carries a salt of its own, so the column records the scheme instead. Setting it first is what
        // decides whether the pepper applies below.
        $this->password_scheme = self::getPasswordSchemeForNewHash();
        $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($this->getSeasonedPassword($password));
    }

    /**
     * The optional `passwordPepper` param is a secret the database does not hold, so a leaked hash cannot be
     * attacked offline. Which scheme a given hash used is recorded in `password_scheme`, so adding, changing or
     * removing the pepper never locks anyone out of an account they can still type the password for — it only
     * marks the hash outdated.
     */
    private function getSeasonedPassword(string $password): string
    {
        return $password . ($this->password_scheme === static::PASSWORD_PEPPER ? self::getPasswordPepper() : '');
    }

    private static function getPasswordSchemeForNewHash(): ?string
    {
        return self::getPasswordPepper() === '' ? null : static::PASSWORD_PEPPER;
    }

    private static function getPasswordPepper(): string
    {
        return (string)(Yii::$app->params['passwordPepper'] ?? '');
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->getSecurity()->generateRandomString();
    }

    /**
     * @return string the token in the clear, which is the only time it exists — the row keeps its HMAC
     */
    public function createVerificationToken(): string
    {
        return $this->createToken(UserToken::TYPE_VERIFICATION);
    }

    /**
     * @return string the token in the clear, which is the only time it exists — the row keeps its HMAC
     */
    public function createPasswordResetToken(): string
    {
        return $this->createToken(UserToken::TYPE_PASSWORD_RESET);
    }

    public function clearVerificationTokens(): void
    {
        $this->deleteTokens(UserToken::TYPE_VERIFICATION);
    }

    public function clearPasswordResetTokens(): void
    {
        $this->deleteTokens(UserToken::TYPE_PASSWORD_RESET);
    }

    public function confirmEmail(): void
    {
        $this->email_confirmed_at ??= new DateTime();
        $this->updateAttributes(['email_confirmed_at' => $this->email_confirmed_at]);

        $this->clearVerificationTokens();
    }

    public function getLatestToken(string $type): ?UserToken
    {
        if (!$this->id) {
            return null;
        }

        return UserToken::find()
            ->whereUser($this->id)
            ->whereType($type)
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();
    }

    private function createToken(string $type): string
    {
        $token = Yii::$app->getSecurity()->generateRandomString();
        UserToken::issue($this->id, $type, $token, $this->tokenLifetime);

        return $token;
    }

    private function deleteTokens(string $type): void
    {
        if ($this->id) {
            UserToken::deleteAll(['user_id' => $this->id, 'type' => $type]);
        }
    }

    public function hasTwoFactorAuthentication(): bool
    {
        return (bool)$this->two_factor_secret;
    }

    public function getTwoFactorAuthenticationSecret(): ?string
    {
        return $this->two_factor_secret === null
            ? null
            : static::decryptTwoFactorAuthenticationSecret($this->two_factor_secret);
    }

    public function setTwoFactorAuthenticationSecret(?string $secret): void
    {
        $this->two_factor_secret = $secret === null
            ? null
            : static::encryptTwoFactorAuthenticationSecret($secret);

        $this->deleteTokens(UserToken::TYPE_RECOVERY_CODE);
    }

    /**
     * @return list<string> the codes in the clear, which is the only time they can be shown — only their hashes
     *     are kept
     */
    public function generateTwoFactorAuthenticationRecoveryCodes(): array
    {
        $this->deleteTokens(UserToken::TYPE_RECOVERY_CODE);
        $codes = [];

        for ($i = 0; $i < static::RECOVERY_CODE_COUNT; $i++) {
            $code = '';

            for ($j = 0; $j < static::RECOVERY_CODE_LENGTH; $j++) {
                $code .= self::RECOVERY_CODE_ALPHABET[random_int(0, strlen(self::RECOVERY_CODE_ALPHABET) - 1)];
            }

            // A recovery code never expires; it is spent instead
            UserToken::issue($this->id, UserToken::TYPE_RECOVERY_CODE, self::normalizeRecoveryCode($code));
            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * Consumes the code it matches: a recovery code is good once, so the row is gone before this returns.
     */
    public function validateTwoFactorAuthenticationRecoveryCode(string $code): bool
    {
        $token = UserToken::find()
            ->whereUser($this->id)
            ->whereType(UserToken::TYPE_RECOVERY_CODE)
            ->whereToken(self::normalizeRecoveryCode($code))
            ->limit(1)
            ->one();

        return (bool)$token?->delete();
    }

    public function getTwoFactorAuthenticationRecoveryCodeCount(): int
    {
        if (!$this->id) {
            return 0;
        }

        return (int)UserToken::find()
            ->whereUser($this->id)
            ->whereType(UserToken::TYPE_RECOVERY_CODE)
            ->count();
    }

    public static function encryptTwoFactorAuthenticationSecret(string $secret): string
    {
        $data = Yii::$app->getSecurity()->encryptByKey($secret, SecretKey::get());
        return self::ENCRYPTED_PREFIX . base64_encode($data);
    }

    public static function decryptTwoFactorAuthenticationSecret(string $secret): ?string
    {
        if (!str_starts_with($secret, self::ENCRYPTED_PREFIX)) {
            // Written before the column was encrypted, and still the secret itself
            return $secret;
        }

        $data = base64_decode(substr($secret, strlen(self::ENCRYPTED_PREFIX)), true);
        $secret = $data === false ? false : Yii::$app->getSecurity()->decryptByKey($data, SecretKey::get());

        return $secret === false ? null : $secret;
    }

    private static function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(trim($code));
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

    /**
     * Deliberately unscoped to the record: the update action renders a user the acting one may not change
     * read-only, so hiding the site owner from the results would only lose the link to them.
     */
    protected function isSearchResultVisible(): bool
    {
        return Yii::$app->has('user') && Yii::$app->getUser()->can(static::AUTH_USER);
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

    /**
     * Creates the token as a side effect: only its HMAC is kept, so the URL cannot be rebuilt afterwards. The
     * address is no longer a parameter — the token finds its own user, and the URL travels through mailboxes,
     * archives and referrers where an address has no business being.
     *
     * @see AccountController::actionConfirm()
     */
    public function createEmailConfirmationUrl(): string
    {
        return Yii::$app->getUrlManager()->createAbsoluteUrl([
            '/admin/account/confirm',
            'code' => $this->createVerificationToken(),
        ]);
    }

    /**
     * @see AccountController::actionReset()
     */
    public function createPasswordResetUrl(): string
    {
        return Yii::$app->getUrlManager()->createAbsoluteUrl([
            '/admin/account/reset',
            'code' => $this->createPasswordResetToken(),
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
            'password_scheme',
            'auth_key',
            'email_confirmed_at',
            'two_factor_secret',
            'login_count',
            'last_login',
            'created_by_user_id',
            'updated_at',
            'created_at',
        ]);
    }

    protected function getAdminNameAttributeValue(): string
    {
        return trim((string)$this->getUsername());
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
        return $this->email_confirmed_at === null;
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
