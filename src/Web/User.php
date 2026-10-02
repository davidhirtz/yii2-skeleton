<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Caching\CacheCounter;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Helpers\CookieHelper;
use Hirtz\Skeleton\Models\UserLogin;
use Override;
use Yii;
use yii\web\Cookie;
use yii\web\MultiFieldSession;
use yii\web\Response;

/**
 * @extends \yii\web\User<\Hirtz\Skeleton\Models\User>
 */
class User extends \yii\web\User
{
    /**
     * The console application has no `user` component, so shared code — a model's blameable attributes, the trail,
     * a search result's visibility — asks for the identity holder instead of assuming a web request.
     */
    public static function current(): ?static
    {
        $user = Yii::$app->has('user') ? Yii::$app->get('user') : null;
        return $user instanceof static ? $user : null;
    }

    /**
     * @var int the cookie lifetime in seconds
     */
    public int $cookieLifetime = 2_592_000;

    /**
     * @var bool whether the role-based access management always returns `false` if user is not logged in
     */
    public bool $disableRbacForGuests = true;

    /**
     * @var bool whether the role-based access management always returns `true` if user is the site owner
     */
    public bool $disableRbacForOwner = true;

    /**
     * @var bool whether users can log in
     */
    public bool $enableLogin = true;

    /**
     * @var bool whether the user can log in without a confirmed email address
     */
    public bool $enableUnconfirmedEmailLogin = true;

    /**
     * @var bool whether users can reset their password
     */
    public bool $enablePasswordReset = true;

    /**
     * @var bool whether users can create new accounts
     */
    public bool $enableSignup = false;

    /**
     * @var bool whether 2FA via Authenticator should be available.
     */
    public bool $enableTwoFactorAuthentication = true;

    /**
     * @var bool whether the guest-facing forms must answer the same for an address that has an account and one
     * that does not. Turning it off restores the messages that name the reason — useful for a closed admin whose
     * support desk relies on them, at the cost of handing out a list of accounts.
     */
    public bool $enableUserEnumerationProtection = true;

    /**
     * @var string|null the IP address of the user
     */
    public ?string $ipAddress = null;

    /**
     * @var int how many failed logins an email address or an IP may accumulate before both are locked out, `0`
     * disables the lockout. A six-digit TOTP code with a discrepancy of one leaves three codes valid per period,
     * so the second factor needs this as much as the password does.
     */
    public int $loginAttemptLimit = 10;

    /**
     * @var int how long a lockout lasts, in seconds. Every further attempt restarts it.
     */
    public int $loginAttemptDuration = 900;

    /**
     * @var int the type written to `user_login`, one of the {@see UserLogin} constants. A flow that logs a user in
     * without naming one is recorded as {@see UserLogin::TYPE_OTHER}.
     */
    public int $loginType = UserLogin::TYPE_OTHER;

    /**
     * @var bool|null whether the auto login cookie is `Secure`, `null` derives it from the request. Pin it on a
     * host that answers on both schemes: a browser refuses a plain HTTP response the right to overwrite or
     * delete a cookie an HTTPS one wrote, so the two would be a cookie nothing can clear.
     */
    public ?bool $cookieSecure = null;

    public $enableAutoLogin = true;
    public $identityClass = \Hirtz\Skeleton\Models\User::class;
    /**
     * @var array<string, mixed>
     */
    public $identityCookie = ['name' => '_auth', 'httpOnly' => true];
    /**
     * @var array<int|string, mixed>|string|null
     */
    public $loginUrl = null;

    /**
     * @var array<int|string, list<string>> the permission names already looked up in this request
     */
    private array $permissionNames = [];

    /**
     * @var array<int|string, array<int|string, bool>> by actor, then target: what {@see canManageUser()} answered
     */
    private array $manageableUsers = [];

    private ?int $permissionCount = null;

    #[Override]
    public function init(): void
    {
        if (!$this->enableLogin) {
            $this->enableUnconfirmedEmailLogin = false;
            $this->enableAutoLogin = false;
        }

        $request = Application::current()->getRequest();

        $this->ipAddress ??= $request->getUserIP();
        $this->identityCookie['secure'] ??= $this->cookieSecure ?? $request->getIsSecureConnection();

        parent::init();
    }

    #[Override]
    public function loginRequired($checkAjax = true, $checkAcceptHeader = true): ?Response
    {
        $request = Application::current()->getRequest();

        // Set flash message for required logins.
        if (!$checkAjax || !$request->getIsAjax()) {
            Application::current()->getSession()->addFlash('error', Yii::t('skeleton', 'USER_ERROR_MUST_LOGIN_VIEW'));
        }

        if ($request->isHtmxRequest()) {
            return Application::current()->getResponse()->setHtmxReload();
        }

        return parent::loginRequired($checkAjax, $checkAcceptHeader);
    }

    /**
     * Yii renews the auto login cookie without ever looking inside it, so one whose auth key the database no
     * longer holds — every cookie issued before a password change, a reset or the v3 upgrade — is handed another
     * full lifetime on every request the session carries, and only fails once that session lapses. Worse, a
     * request that renews it while a fresh one is still in flight puts the stale value back, so the logout
     * repeats for as long as the cookie survives. Validate it here and drop it instead.
     */
    #[Override]
    protected function renewIdentityCookie(): void
    {
        $value = Application::current()->getRequest()->getCookies()->getValue($this->identityCookie['name']);

        if ($value === null) {
            return;
        }

        if ($this->isIdentityCookieValid($value)) {
            parent::renewIdentityCookie();
            return;
        }

        $this->removeIdentityCookie();
    }

    /**
     * The auto login cookie is a credential, so the logout has to remove it whatever scope it was written under. A
     * `Domain` the installation has since gained or lost — a tenant's, an edited `cookie_domain` — leaves a second
     * cookie of the same name behind that the scoped deletion never reaches, and the next request logs the account
     * straight back in. {@see \yii\web\CookieCollection} is keyed by name, so the host-only deletion cannot go
     * through it and is sent as a header of its own.
     */
    #[Override]
    protected function removeIdentityCookie(): void
    {
        $cookie = Yii::$container->get(Cookie::class, [], $this->identityCookie);

        if ($cookie->domain !== '') {
            Application::current()->getResponse()->getHeaders()
                ->add('Set-Cookie', CookieHelper::getExpiredHeader($cookie));
        }

        parent::removeIdentityCookie();
    }

    private function isIdentityCookieValid(mixed $value): bool
    {
        $identity = $this->getIdentity();

        if (!$identity || !is_string($value)) {
            return false;
        }

        $data = json_decode($value, true);

        if (!is_array($data) || count($data) !== 3) {
            return false;
        }

        [$id, $authKey] = $data;

        return is_string($authKey)
            && (string)$id === (string)$identity->getId()
            && $identity->validateAuthKey($authKey);
    }

    /**
     * @param \Hirtz\Skeleton\Models\User $identity
     */
    #[Override]
    protected function afterLogin($identity, $cookieBased, $duration): void
    {
        $session = Application::current()->getSession();
        $session->set('last_login_timestamp', $identity->last_login?->getTimestamp());

        // The login answers whatever {@see static::loginRequired()} flashed, and a flash removed after access is
        // removed only once something reads it — so one added to a response that renders none, an htmx request
        // answered with a reload among them, would surface on the page after the login.
        $session->removeFlash('error');

        // The page the login was typed into was rendered for a guest, and a swap only ever reaches `#wrap`: its
        // navbar, and the login-required error already drawn into `#flashes`, would both outlive the login. A
        // cookie login is exempt — it restores the identity the page was already rendered for.
        if (!$cookieBased && Application::current()->getRequest()->isHtmxRequest()) {
            Application::current()->getResponse()->setHtmxReload();
        }

        if ($session instanceof MultiFieldSession) {
            $session->writeCallback = fn () => [
                'ip_address' => ($ipAddress = Application::current()->getRequest()->getUserIP()) ? inet_pton($ipAddress) : null,
                'user_id' => $identity->id,
            ];
        }

        $identity->login_count++;
        $identity->last_login = new DateTime();

        // Whoever just proved they know the password has no use for a reset link, and the one in their inbox
        // must not stay live behind them.
        $identity->clearPasswordResetTokens();

        if ($cookieBased) {
            $this->loginType = UserLogin::TYPE_COOKIE;
        }

        $this->insertLogin($identity);
        $identity->update(false);

        parent::afterLogin($identity, $cookieBased, $duration);
    }

    /**
     * @param \Hirtz\Skeleton\Models\User $identity
     */
    #[Override]
    protected function afterLogout($identity): void
    {
        $session = Application::current()->getSession();

        if ($session instanceof MultiFieldSession) {
            $session->writeCallback = fn () => [
                'user_id' => null,
            ];
        }

        if (Application::current()->getRequest()->isHtmxRequest()) {
            Application::current()->getResponse()->setHtmxReload();
        }

        parent::afterLogout($identity);
    }

    /**
     * A signup that does not log the user in is recorded all the same: the signup form counts an origin's signups
     * by these rows.
     */
    public function insertSignup(\Hirtz\Skeleton\Models\User $user): void
    {
        $this->insertLogin($user, UserLogin::TYPE_SIGNUP, new DateTime());
    }

    /**
     * A password change rotates the auth key every auto login cookie carries, so the acting user's is written again:
     * "remember me" must survive the user's own change.
     */
    public function resendIdentityCookie(): void
    {
        $identity = $this->getIdentity(false);
        $value = Application::current()->getRequest()->getCookies()->getValue($this->identityCookie['name']);

        if (!$this->enableAutoLogin || !$identity || !is_string($value)) {
            return;
        }

        $data = json_decode($value, true);

        if (is_array($data) && count($data) === 3 && is_int($data[2]) && $data[2] > 0) {
            $this->sendIdentityCookie($identity, $data[2]);
        }
    }

    private function insertLogin(\Hirtz\Skeleton\Models\User $user, ?int $type = null, ?DateTime $createdAt = null): void
    {
        $browser = Application::current()->getRequest()->getUserAgent();

        if (is_string($browser)) {
            $browser = mb_substr($browser, 0, 255, Yii::$app->charset);
        }

        $ipAddress = $this->ipAddress ?: Application::current()->getRequest()->getUserIP();
        $ipAddress = $ipAddress ? inet_pton($ipAddress) : null;

        $columns = [
            'user_id' => $user->id,
            'type' => $type ?? $this->loginType,
            'browser' => $browser,
            'ip_address' => $ipAddress,
            'created_at' => $createdAt ?? $user->last_login,
        ];

        UserLogin::getDb()->createCommand()->insert(UserLogin::tableName(), $columns)->execute();
    }

    /**
     * @param array<string, mixed> $params
     */
    #[Override]
    public function can($permissionName, $params = [], $allowCaching = true): bool
    {
        if ($this->disableRbacForGuests && $this->getIsGuest()) {
            return false;
        }

        if ($this->disableRbacForOwner && $this->identity?->isOwner()) {
            return true;
        }

        $target = $params['user'] ?? null;

        if ($target instanceof \Hirtz\Skeleton\Models\User && !$this->canManageUser($target)) {
            return false;
        }

        return parent::can($permissionName, $params, $allowCaching);
    }

    /**
     * Guards the site owner, and anyone whose permissions the acting user does not already hold: managing a user
     * means setting a password, generating a reset token and clearing a second factor, so without this it is a
     * takeover of every account it reaches — including the ones that can grant permissions.
     */
    public function canManageUser(\Hirtz\Skeleton\Models\User $user): bool
    {
        $id = $this->getId();

        if ($id === null || $user->id === $id) {
            return true;
        }

        return $this->manageableUsers[$id][$user->id] ??= $this->isManageableUser($user, $id);
    }

    private function isManageableUser(\Hirtz\Skeleton\Models\User $user, int|string $id): bool
    {
        if ($user->isOwner()) {
            return false;
        }

        $held = $this->getPermissionNames($id);
        $this->permissionCount ??= count(Yii::$app->getAuthManager()->getPermissions());

        // Nobody holds more than everything, so the usual case — an administrator — costs no lookup at all
        if (count($held) >= $this->permissionCount) {
            return true;
        }

        return !array_diff($this->getPermissionNames($user->id), $held);
    }

    /**
     * @return list<string>
     */
    private function getPermissionNames(int|string $userId): array
    {
        return $this->permissionNames[$userId] ??= array_keys(
            Yii::$app->getAuthManager()->getPermissionsByUser($userId)
        );
    }

    public function isLoginAttemptLimitReached(?string $email = null): bool
    {
        if ($this->loginAttemptLimit < 1) {
            return false;
        }

        foreach ($this->getLoginAttemptCacheKeys($email) as $key) {
            if (CacheCounter::get($key) >= $this->loginAttemptLimit) {
                return true;
            }
        }

        return false;
    }

    public function addFailedLoginAttempt(?string $email = null): void
    {
        if ($this->loginAttemptLimit < 1) {
            return;
        }

        foreach ($this->getLoginAttemptCacheKeys($email) as $key) {
            CacheCounter::increment($key, $this->loginAttemptDuration);
        }
    }

    /**
     * The account's count only: the origin's expires on its own, or one valid account logged into between guesses
     * would keep an address spraying passwords at every other account forever.
     */
    public function resetFailedLoginAttempts(?string $email = null): void
    {
        if ($key = $this->getEmailLoginAttemptCacheKey($email)) {
            Yii::$app->getCache()->delete($key);
        }
    }

    /**
     * Both the account and the origin are counted: the first stops a password being guessed, the second stops one
     * password being tried against every account.
     *
     * @return list<array{string, string, string, string}>
     */
    private function getLoginAttemptCacheKeys(?string $email): array
    {
        $keys = [];

        if ($key = $this->getEmailLoginAttemptCacheKey($email)) {
            $keys[] = $key;
        }

        if ($this->ipAddress) {
            $keys[] = [self::class, 'login-attempts', 'ip', $this->getLoginAttemptOrigin($this->ipAddress)];
        }

        return $keys;
    }

    /**
     * An IPv6 address is counted by its /64: a single host is given the whole network and can rotate through it.
     */
    private function getLoginAttemptOrigin(string $ipAddress): string
    {
        $packed = @inet_pton($ipAddress);

        return is_string($packed) && strlen($packed) === 16
            ? bin2hex(substr($packed, 0, 8)) . '/64'
            : $ipAddress;
    }

    /**
     * @return array{string, string, string, string}|null
     */
    private function getEmailLoginAttemptCacheKey(?string $email): ?array
    {
        $email = mb_strtolower(trim((string)$email));
        return $email !== '' ? [self::class, 'login-attempts', 'email', $email] : null;
    }

    /**
     * Rotating the auth key only invalidates the auto login cookies — every session row already written for this
     * user keeps working until it expires, so a password change has to delete them by hand.
     *
     * @return int the number of sessions destroyed
     */
    public function destroyOtherSessions(?\Hirtz\Skeleton\Models\User $user = null): int
    {
        $user ??= $this->getIdentity();
        $session = Application::current()->getSession();

        if (!$user?->id || !$session instanceof DbSession) {
            return 0;
        }

        return $session->destroyUserSessions(
            $user->id,
            $session->getIsActive() ? $session->getId() : null
        );
    }

    /**
     * A flow that logs a user in without asking for a password — a password reset, an email confirmation, a signup —
     * must not skip the second factor. It has no code to check, so it has to refuse the login instead.
     */
    public function isTwoFactorAuthenticationRequired(\Hirtz\Skeleton\Models\User $user): bool
    {
        return $this->enableTwoFactorAuthentication && $user->hasTwoFactorAuthentication();
    }

    public function isLoginEnabled(): bool
    {
        return !!$this->enableLogin;
    }

    public function isUnconfirmedEmailLoginEnabled(): bool
    {
        return !!$this->enableUnconfirmedEmailLogin;
    }

    public function isPasswordResetEnabled(): bool
    {
        return !!$this->enablePasswordReset;
    }

    public function isSignupEnabled(): bool
    {
        return $this->enableSignup;
    }
}
