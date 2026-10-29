<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use DateTimeZone;
use IntlTimeZone;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\SecretKey;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Validators\UniqueValidator;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;
use Hirtz\Skeleton\Db\DateTime;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string|null $name
 * @property string $email
 * @property DateTime|null $email_confirmed_at
 * @property string|null $password_hash
 * @property string|null $password_scheme
 * @property string $language
 * @property string|null $timezone
 * @property bool|int $show_hints
 * @property string|null $color_scheme
 * @property string|null $auth_key
 * @property string|null $two_factor_secret
 * @property int|null $two_factor_timeslice
 * @property bool|int $is_owner
 * @property int|null $created_by_user_id
 * @property int $login_count
 * @property DateTime|null $last_login
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 *
 * @property-read User|null $created
 *
 * @mixin TrailBehavior
 */
class User extends ActiveRecord implements CustomAttributeInterface, IdentityInterface, SearchableInterface, StatusAttributeInterface, TrailModelInterface
{
    use AdminModelTrait;
    use CustomAttributesTrait;
    use ModuleTrait;
    use SearchableTrait;
    use StatusAttributeTrait;
    use TrailModelTrait;

    /**
     * A role marker of {@see \yii\filters\AccessRule}, mixable with permission names wherever roles are declared.
     */
    final public const string ROLE_ANY = '*';
    final public const string ROLE_AUTHENTICATED = '@';

    final public const string AUTH_USER = 'user';
    final public const string AUTH_USER_ASSIGN = 'authUpdate';
    final public const string AUTH_ROLE_ADMIN = 'admin';

    /**
     * Roles never inherit, and `system` and `tenant` are {@see static::AUTH_ROLE_ADMIN}'s alone: a guard an
     * administrator should also pass names both roles.
     */
    final public const string AUTH_ROLE_MANAGER = 'manager';

    /**
     * A hash whose scheme disagrees with the configured `passwordPepper` is outdated, not broken: the next
     * successful login rewrites it.
     */
    final public const string PASSWORD_PEPPER = 'pepper';

    /**
     * Anything else, `null` included, leaves the scheme to the browser's `prefers-color-scheme`.
     */
    final public const string COLOR_SCHEME_LIGHT = 'light';
    final public const string COLOR_SCHEME_DARK = 'dark';
    final public const array COLOR_SCHEMES = [self::COLOR_SCHEME_LIGHT, self::COLOR_SCHEME_DARK];

    /**
     * Marks an encrypted secret, so a row that predates the encryption is still readable.
     */
    private const string ENCRYPTED_PREFIX = 'enc:';

    private const string RECOVERY_CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    final public const int RECOVERY_CODE_LENGTH = 10;

    final public const int RECOVERY_CODE_COUNT = 8;

    public int $nameMinLength = 3;

    public int $nameMaxLength = 32;

    public string|false $namePattern = '/^\d*[a-z][a-z0-9\.-]*[a-z0-9]$/si';

    public int $passwordMinLength = 8;

    /**
     * bcrypt silently truncates at 72 bytes, so nothing longer may be accepted as if it were the password.
     */
    public int $passwordMaxLength = 72;

    public bool $requireName = true;

    /**
     * A token lives on in every mail archive along the way, so it must not be usable forever.
     */
    public int $tokenLifetime = 86400;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
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
                ['timezone'],
                'filter',
                'filter' => static::getListedTimezone(...),
            ],
            [
                ['language', 'timezone'],
                DynamicRangeValidator::class,
                'integerOnly' => false,
            ],
            [
                ['show_hints'],
                'boolean',
            ],
            [
                ['color_scheme'],
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
                'message' => Yii::t('skeleton', 'USER_NAME_FORMAT_ERROR'),
                'skipOnError' => true,
                'when' => fn () => $this->namePattern !== false,
            ],
            [
                ['name'],
                UniqueValidator::class,
                'message' => Yii::t('skeleton', 'USER_NAME_UNIQUE_ERROR'),
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
                'message' => Yii::t('skeleton', 'USER_EMAIL_UNIQUE_ERROR'),
                'skipOnError' => true,
                'when' => fn () => $this->isAttributeChanged('email')
            ],
        ];
    }

    /**
     * A record without a key matches nothing.
     */
    public function validateAuthKey($authKey): bool
    {
        $key = $this->getAuthKey();
        return $key !== null && hash_equals($key, $authKey);
    }

    public function validatePassword(string $password): bool
    {
        return (bool)$this->password_hash
            && Yii::$app->getSecurity()->validatePassword($this->getSeasonedPassword($password), $this->password_hash);
    }

    /**
     * True for a legacy salt, a pepper added or removed since, or a cost below the security component's current
     * one; a successful login rewrites it.
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

    /**
     * A language the admin stopped offering is replaced rather than refused: with one language the field is hidden.
     */
    #[Override]
    public function beforeValidate(): bool
    {
        if (!$this->isAttributeChanged('language')) {
            $this->language = static::getModule()->getOfferedLanguage($this->language);
        }

        return parent::beforeValidate();
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->generateAuthKey();
        }

        return parent::beforeSave($insert);
    }

    /**
     * A token proves nothing about a new address, and a reset link must not outlive the password it was to replace.
     *
     * @param array<string, mixed> $changedAttributes
     */
    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if (!$insert) {
            if (array_key_exists('email', $changedAttributes)) {
                $this->clearVerificationTokens();
            }

            if (array_key_exists('email', $changedAttributes) || array_key_exists('password_hash', $changedAttributes)) {
                $this->clearPasswordResetTokens();
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function delete(): false|int
    {
        if (!$this->isDeletable()) {
            $this->addError('id', $this->isOwner()
                ? Yii::t('skeleton', 'USER_DELETE_OWNER_ERROR')
                : Yii::t('skeleton', 'USER_DELETE_ERROR'));

            return false;
        }

        return parent::delete();
    }

    /**
     * @return UserQuery<static>
     */
    public function getCreated(): UserQuery
    {
        /** @var UserQuery<static> $query */
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

    /**
     * Every caller also logs out the other sessions, so the agents connected through MCP go too.
     */
    public function afterPasswordChange(): void
    {
        $trail = Trail::instantiateByType(Trail::TYPE_PASSWORD);
        $trail->model_class = User::class;
        $trail->model_id = (string)$this->id;
        $trail->insert();

        OAuthGrant::deleteAll(['user_id' => $this->id]);
        UserToken::deleteAll(['user_id' => $this->id, 'type' => [UserToken::TYPE_MCP_READ, UserToken::TYPE_MCP_WRITE]]);
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
     * attacked offline.
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
     * The only time the token exists in the clear: the row keeps its HMAC.
     */
    public function createVerificationToken(): string
    {
        return $this->createToken(UserToken::TYPE_VERIFICATION);
    }

    /**
     * The only time the token exists in the clear: the row keeps its HMAC.
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

        $this->two_factor_timeslice = null;
        $this->deleteTokens(UserToken::TYPE_RECOVERY_CODE);
    }

    /**
     * The only time the codes can be shown: only their hashes are kept.
     *
     * @return list<string>
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

        return is_string($secret) ? $secret : null;
    }

    private static function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function getAdminRoute(): array
    {
        return $this->id ? ['/admin/user/update', 'id' => $this->id] : ['/admin/user/index'];
    }

    public function getAdminIndexBreadcrumb(): Breadcrumb
    {
        return new Breadcrumb(Yii::t('skeleton', 'USER_PLURAL'), ['/admin/user/index']);
    }

    public function getPermissionName(): string
    {
        return self::AUTH_USER;
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
     * Unscoped on purpose: the update action renders a user the acting one may not change read-only, so hiding the
     * owner would only lose the link to them.
     */
    protected function isSearchResultVisible(): bool
    {
        return WebUser::current()?->can(static::AUTH_USER) ?? false;
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
     * Creates the token as a side effect: only its HMAC is kept, so the URL cannot be rebuilt afterwards.
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

    /**
     * @return list<Status>
     */
    protected function getDefaultStatuses(): array
    {
        return [
            Status::make(static::STATUS_DISABLED)
                ->name(Yii::t('skeleton', 'STATUS_DISABLED_NAME'))
                ->icon('exclamation-triangle'),
            Status::make(static::STATUS_ENABLED)
                ->name(Yii::t('skeleton', 'STATUS_ENABLED_NAME'))
                ->icon('user'),
        ];
    }

    public function getStatusName(): string
    {
        if ($this->isOwner()) {
            return Yii::t('skeleton', 'USER_OWNER_TEXT');
        }

        return $this->getStatus()?->getName() ?? '';
    }

    public function getStatusIcon(): string
    {
        return !$this->isOwner() ? ($this->getStatus()?->getIcon() ?? '') : 'star';
    }

    /**
     * No `#[Override]`: the method comes from a trait this class uses itself.
     */
    public function isStatusUpdatable(): bool
    {
        $userId = WebUser::current()?->getId();
        return !$this->isOwner() && ($userId === null || $this->id !== $userId);
    }

    /**
     * @return list<string>
     */
    public function getTrailAttributes(): array
    {
        return array_values(array_diff($this->attributes(), [
            $this->getCustomAttributesColumn(),
            'password_hash',
            'password_scheme',
            'auth_key',
            'email_confirmed_at',
            'two_factor_secret',
            'two_factor_timeslice',
            'login_count',
            'last_login',
            'created_by_user_id',
            'updated_at',
            'created_at',
        ]));
    }

    protected function getAdminNameAttributeValue(): string
    {
        return trim((string)$this->getUsername());
    }

    public function getAdminType(): string
    {
        return Yii::t('skeleton', 'USER_SINGULAR');
    }

    protected function getDefaultAdminIcon(): ?string
    {
        return 'user';
    }

    public function isDeletable(): bool
    {
        return !$this->isOwner();
    }

    public function isOwner(): bool
    {
        return (bool)$this->is_owner;
    }

    public function showsHints(): bool
    {
        return (bool)$this->show_hints;
    }

    public function getColorScheme(): ?string
    {
        return in_array($this->color_scheme, self::COLOR_SCHEMES, true) ? $this->color_scheme : null;
    }

    /**
     * Resolved as `get<Plural>()` by {@see DynamicRangeValidator} and the `SelectField`; the empty value is the
     * select's prompt, not an item here.
     *
     * @noinspection PhpUnused
     * @return array<string, string>
     */
    public static function getColorSchemes(): array
    {
        return [
            self::COLOR_SCHEME_LIGHT => Yii::t('skeleton', 'USER_COLOR_SCHEME_LIGHT_NAME'),
            self::COLOR_SCHEME_DARK => Yii::t('skeleton', 'USER_COLOR_SCHEME_DARK_NAME'),
        ];
    }

    public function isUnconfirmed(): bool
    {
        return $this->email_confirmed_at === null;
    }

    /**
     * @see \Hirtz\Skeleton\Widgets\Forms\Fields\SelectField::getItemsFromModel()
     * @return array<string, string>
     * @noinspection PhpUnused
     */
    public static function getLanguages(): array
    {
        $i18n = Yii::$app->getI18n();
        $languages = [];

        foreach (self::getModule()->getLanguages() as $language) {
            $languages[$language] = $i18n->getLabel($language);
        }

        return $languages;
    }

    /**
     * Chromium reports the legacy name of some zones (`Asia/Calcutta`), which PHP reads but does not list.
     */
    public static function getListedTimezone(mixed $timezone): mixed
    {
        $identifiers = DateTimeZone::listIdentifiers();

        if (!is_string($timezone) || in_array($timezone, $identifiers, true)) {
            return $timezone;
        }

        $count = IntlTimeZone::countEquivalentIDs($timezone);

        for ($i = 0; $i < $count; $i++) {
            $id = IntlTimeZone::getEquivalentID($timezone, $i);

            if (in_array($id, $identifiers, true)) {
                return $id;
            }
        }

        return $timezone;
    }

    /**
     * @noinspection PhpUnused
     * @return array<string, string>
     */
    public static function getTimezones(): array
    {
        return array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers());
    }

    #[Override]
    public function attributeHints(): array
    {
        return [
            ...parent::attributeHints(),
            'show_hints' => Yii::t('skeleton', 'USER_SHOW_HINTS_HINT'),
            'color_scheme' => Yii::t('skeleton', 'USER_COLOR_SCHEME_HINT'),
        ];
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'id' => Yii::t('skeleton', 'MODEL_ID_LABEL'),
            'name' => Yii::t('skeleton', 'USER_NAME_LABEL'),
            'email' => Yii::t('skeleton', 'USER_EMAIL_LABEL'),
            'password' => Yii::t('skeleton', 'USER_PASSWORD_LABEL'),
            'language' => Yii::t('skeleton', 'USER_LANGUAGE_LABEL'),
            'timezone' => Yii::t('skeleton', 'USER_TIMEZONE_LABEL'),
            'show_hints' => Yii::t('skeleton', 'USER_SHOW_HINTS_LABEL'),
            'color_scheme' => Yii::t('skeleton', 'USER_COLOR_SCHEME_LABEL'),
            'login_count' => Yii::t('skeleton', 'USER_LOGIN_COUNT_LABEL'),
            'last_login' => Yii::t('skeleton', 'USER_LAST_LOGIN_LABEL'),
            'is_owner' => Yii::t('skeleton', 'USER_IS_OWNER_LABEL'),
            'updated_at' => Yii::t('skeleton', 'MODEL_UPDATED_AT_LABEL'),
            'created_at' => Yii::t('skeleton', 'MODEL_CREATED_AT_LABEL'),
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
