<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Caching\CacheCounter;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Helpers\CookieHelper;
use Hirtz\Skeleton\Helpers\Url;
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
     * `null` under the console, which has no `user` component, so shared code never assumes a web request.
     */
    public static function current(): ?static
    {
        $user = Yii::$app->has('user') ? Yii::$app->get('user') : null;
        return $user instanceof static ? $user : null;
    }

    public int $cookieLifetime = 2_592_000;

    public bool $disableRbacForGuests = true;

    public bool $disableRbacForOwner = true;

    public bool $enableLogin = true;

    public bool $enableUnconfirmedEmailLogin = true;

    public bool $enablePasswordReset = true;

    public bool $enableSignup = false;

    public bool $enableTwoFactorAuthentication = true;

    /**
     * Off restores the guest-facing messages that name the reason, at the cost of handing out a list of accounts.
     */
    public bool $enableUserEnumerationProtection = true;

    public ?string $ipAddress = null;

    /**
     * Carries the return URL of a request without a session, which {@see loginRequired()} does not start.
     */
    public string $returnUrlQueryParam = 'returnUrl';

    /**
     * `0` disables the lockout, which the second factor needs too: a discrepancy of one leaves three codes valid.
     */
    public int $loginAttemptLimit = 10;

    /**
     * Every further attempt restarts it.
     */
    public int $loginAttemptDuration = 900;

    /**
     * Strangers guessing the password lock the account for everyone but its owner's known browsers (OWASP).
     */
    public bool $enableDeviceCookies = true;

    /**
     * `secure` follows the auto login cookie's.
     *
     * @var array<string, mixed>
     */
    public array $deviceCookie = ['name' => '_device', 'httpOnly' => true, 'sameSite' => Cookie::SAME_SITE_LAX];

    /**
     * Renewed by every login.
     */
    public int $deviceCookieLifetime = 31_536_000;

    /**
     * One of the {@see UserLogin} constants.
     */
    public int $loginType = UserLogin::TYPE_OTHER;

    /**
     * `null` derives it from the request; pin it on a host answering both schemes, since no HTTP response can clear a
     * cookie an HTTPS one wrote.
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
     * @var array<int|string, list<string>>
     */
    private array $permissionNames = [];

    /**
     * @var array<int|string, list<string>>
     */
    private array $roleNames = [];

    /**
     * By actor, then target.
     *
     * @var array<int|string, array<int|string, bool>>
     */
    private array $manageableUsers = [];

    private ?int $permissionCount = null;

    /**
     * The nonce by normalised address and cookie value, if the cookie is that account's.
     *
     * @var array<string, string|null>
     */
    private array $loginDevices = [];

    #[Override]
    public function init(): void
    {
        if (!$this->enableLogin) {
            $this->enableUnconfirmedEmailLogin = false;
            $this->enableAutoLogin = false;
        }

        $request = Request::current();

        $this->ipAddress ??= $request?->getUserIP();
        $this->identityCookie['secure'] ??= $this->cookieSecure ?? $request?->getIsSecureConnection() ?? true;
        $this->deviceCookie['secure'] ??= $this->identityCookie['secure'];

        parent::init();
    }

    public static function setSessionlessIdentity(?\Hirtz\Skeleton\Models\User $identity): self
    {
        /** @var self $webuser */
        $webuser = Yii::createObject([
            'class' => self::class,
            'identityClass' => \Hirtz\Skeleton\Models\User::class,
            'enableSession' => false,
            'enableAutoLogin' => false,
        ]);

        $webuser->setIdentity($identity);
        Yii::$app->set('user', $webuser);

        return $webuser;
    }

    #[Override]
    public function loginRequired($checkAjax = true, $checkAcceptHeader = true): ?Response
    {
        $request = Application::current()->getRequest();
        $hasSession = Application::current()->getSession()->isStarted();

        // A flash or a return URL in the session would write a session row for every bot asking for the admin.
        if ($hasSession && (!$checkAjax || !$request->getIsAjax())) {
            Application::current()->getSession()->addFlash('error', Yii::t('skeleton', 'LOGIN_REQUIRED_ERROR'));
        }

        if ($request->isHtmxRequest()) {
            return Application::current()->getResponse()->setHtmxReload();
        }

        if ($hasSession || !$this->enableSession || !$request->getIsGet() || ($checkAjax && $request->getIsAjax())) {
            return parent::loginRequired($checkAjax, $checkAcceptHeader);
        }

        $loginUrl = $this->loginUrl;

        if (is_array($loginUrl)) {
            $this->loginUrl = [...$loginUrl, $this->returnUrlQueryParam => $request->getAbsoluteUrl()];
        }

        $this->enableSession = false;

        try {
            return parent::loginRequired($checkAjax, $checkAcceptHeader);
        } finally {
            $this->enableSession = true;
            $this->loginUrl = $loginUrl;
        }
    }

    /**
     * A URL on this host or its draft host only, so the parameter cannot send a login anywhere else.
     */
    public function isReturnUrl(string $url): bool
    {
        if (str_contains($url, '\\') || preg_match('/[\x00-\x1f]/', $url)) {
            return false;
        }

        $manager = Yii::$app->getUrlManager();

        return Url::isLocalPath($url)
            || str_starts_with($url, $manager->getHostInfo() . '/')
            || str_starts_with($url, $manager->getDraftHostInfo() . '/');
    }

    /**
     * Yii renews the auto login cookie unchecked, so one with a stale auth key would live on (and a concurrent renewal
     * put it back): it is validated and dropped here.
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
     * A twin under a stale `Domain` would log the account straight back in, and `CookieCollection` is keyed by name,
     * so its deletion is a header of its own.
     */
    #[Override]
    protected function removeIdentityCookie(): void
    {
        $cookie = Yii::$container->get(Cookie::class, [], $this->identityCookie);
        $host = (string)Application::current()->getRequest()->getHostName();

        foreach (CookieHelper::getExpiredTwinHeaders($cookie, $host) as $header) {
            Application::current()->getResponse()->getHeaders()->add('Set-Cookie', $header);
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

        $this->sendDeviceCookie($identity);

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
     * Recorded even without a login, since the signup form counts an origin's signups by these rows.
     */
    public function insertSignup(\Hirtz\Skeleton\Models\User $user): void
    {
        $this->insertLogin($user, UserLogin::TYPE_SIGNUP, new DateTime());
    }

    /**
     * A password change rotates the auth key, so the acting user's auto login and device cookies are written again.
     */
    public function resendIdentityCookie(): void
    {
        $identity = $this->getIdentity(false);

        if ($identity) {
            $this->sendDeviceCookie($identity);

            // The session holds the key it was opened with, which no longer validates on the next request.
            if ($this->enableSession) {
                Application::current()->getSession()->set($this->authKeyParam, $identity->getAuthKey());
            }
        }

        $value = Application::current()->getRequest()->getCookies()->getValue($this->identityCookie['name']);

        if (!$this->enableAutoLogin || !$identity || !is_string($value)) {
            return;
        }

        $data = json_decode($value, true);

        if (is_array($data) && count($data) === 3 && is_int($data[2]) && $data[2] > 0) {
            $this->sendIdentityCookie($identity, $data[2]);
        }
    }

    /**
     * The signature is keyed by the auth key, so rotating it invalidates every device cookie the account handed out.
     */
    public function sendDeviceCookie(\Hirtz\Skeleton\Models\User $identity): void
    {
        if (!$this->enableDeviceCookies || !$identity->auth_key) {
            return;
        }

        $nonce = Yii::$app->getSecurity()->generateRandomString();

        $cookie = Yii::$container->get(Cookie::class, [], [
            ...$this->deviceCookie,
            'value' => json_encode([$identity->id, $nonce, $this->getDeviceSignature($identity, $nonce)]),
            'expire' => time() + $this->deviceCookieLifetime,
        ]);

        Application::current()->getResponse()->getCookies()->add($cookie);
        $this->loginDevices = [];
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
     * Managing a user can take the account over, so the owner and anyone holding what the actor lacks are guarded.
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

        // A guard naming a role grants what no permission shows; an administrator passes every guard another role does
        $roles = $this->getRoleNames($id);

        if (!in_array(\Hirtz\Skeleton\Models\User::AUTH_ROLE_ADMIN, $roles, true)
            && array_diff($this->getRoleNames($user->id), $roles)) {
            return false;
        }

        $held = $this->getPermissionNames($id);
        $this->permissionCount ??= count(Yii::$app->getAuthManager()->getPermissions());

        // Nobody holds more than everything, so the usual case — an administrator — costs no permission lookup
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

    /**
     * @return list<string>
     */
    private function getRoleNames(int|string $userId): array
    {
        return $this->roleNames[$userId] ??= array_keys(Yii::$app->getAuthManager()->getRolesByUser($userId));
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

    /**
     * A known browser's failures count against that browser alone, so strangers cannot use up its owner's attempts.
     */
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
     * The account's count only, or one valid login between guesses would let an address spray passwords forever.
     */
    public function resetFailedLoginAttempts(?string $email = null): void
    {
        foreach ([$this->getEmailLoginAttemptCacheKey($email), $this->getDeviceLoginAttemptCacheKey($email)] as $key) {
            if ($key) {
                Yii::$app->getCache()->delete($key);
            }
        }
    }

    /**
     * The origin is counted beside the account (or known browser), which stops one password tried on every account.
     *
     * @return list<array{string, string, string, string}>
     */
    private function getLoginAttemptCacheKeys(?string $email): array
    {
        $keys = [];

        if ($key = $this->getDeviceLoginAttemptCacheKey($email) ?? $this->getEmailLoginAttemptCacheKey($email)) {
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
     * @return array{string, string, string, string}|null
     */
    private function getDeviceLoginAttemptCacheKey(?string $email): ?array
    {
        $nonce = $this->getLoginDevice($email);
        return $nonce !== null ? [self::class, 'login-attempts', 'device', $nonce] : null;
    }

    /**
     * The nonce of the request's device cookie, if it was issued to the account of `$email`.
     */
    private function getLoginDevice(?string $email): ?string
    {
        $email = mb_strtolower(trim((string)$email));

        if (!$this->enableDeviceCookies || $email === '') {
            return null;
        }

        $value = Application::current()->getRequest()->getCookies()->getValue($this->deviceCookie['name']);

        if (!is_string($value)) {
            return null;
        }

        $memo = "$email\n$value";

        if (array_key_exists($memo, $this->loginDevices)) {
            return $this->loginDevices[$memo];
        }

        $data = json_decode($value, true);

        if (!is_array($data) || count($data) !== 3 || !is_string($data[1] ?? null) || !is_string($data[2] ?? null)) {
            return $this->loginDevices[$memo] = null;
        }

        [$id, $nonce, $signature] = $data;

        $user = \Hirtz\Skeleton\Models\User::find()
            ->andWhereEmail($email)
            ->limit(1)
            ->one();

        $isValid = $user?->auth_key
            && is_scalar($id)
            && (string)$user->id === (string)$id
            && hash_equals($this->getDeviceSignature($user, $nonce), $signature);

        return $this->loginDevices[$memo] = $isValid ? $nonce : null;
    }

    private function getDeviceSignature(\Hirtz\Skeleton\Models\User $user, string $nonce): string
    {
        return hash_hmac('sha256', "device|$user->id|$nonce", (string)$user->auth_key);
    }

    /**
     * Rotating the auth key leaves every session row working, so a password change deletes them by hand.
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

    public function revokeOtherSessions(?\Hirtz\Skeleton\Models\User $user = null): int
    {
        $user ??= $this->getIdentity();

        if (!$user) {
            return 0;
        }

        $user->generateAuthKey();
        $user->updateAttributes(['auth_key' => $user->auth_key]);

        $count = $this->destroyOtherSessions($user);
        $identity = $this->getIdentity(false);

        if ($identity?->id === $user->id) {
            $identity->auth_key = $user->auth_key;
            $this->resendIdentityCookie();
        }

        return $count;
    }

    /**
     * A flow logging a user in without a password has no code to check, so it refuses the login rather than skip 2FA.
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
