<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\Console\Controllers\TrailController;
use Hirtz\Skeleton\Console\Controllers\UserLoginController;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Models\AuthItem;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Web\User as WebUser;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use Override;
use Yii;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;
use yii\web\Session;

class Module extends \Hirtz\Skeleton\Base\Module
{
    final public const string AUTH_SYSTEM = 'system';

    final public const string TRANSLATION_LAYOUT_INLINE = 'inline';
    final public const string TRANSLATION_LAYOUT_TABS = 'tabs';

    /**
     * Must match the literal `includes/aside.ts` writes to the aside cookie and `data-aside`.
     */
    final public const string ASIDE_COLLAPSED = 'collapsed';

    /**
     * Only a production request reaches it, so nothing but {@see \Hirtz\Skeleton\Tests\Modules\Admin\ModuleTest}
     * notices the day the view moves.
     */
    final public const string ERROR_VIEW = '@skeleton/../resources/views/admin/dashboard/error.php';

    /**
     * Also switches the index writes of {@see \Hirtz\Skeleton\Behaviors\SearchBehavior} and the `search` commands.
     */
    public bool $enableSearch = true;

    /**
     * `inline` or `tabs`, the default until an editor switches, which is remembered per browser.
     */
    public string $translationLayout = self::TRANSLATION_LAYOUT_INLINE;

    /**
     * Defaults to the content languages, so a project whose content languages the admin has no translation for
     * names its own.
     *
     * @var string[]|null
     */
    public ?array $languages = null;

    public string $languageSessionKey = 'language';

    /**
     * Unsigned, since `includes/aside.ts` writes it — see {@see isAsideCollapsed()}.
     */
    public string $asideCookieName = '_aside';

    /**
     * `null` derives `Secure` from the request.
     */
    public ?bool $asideCookieSecure = null;

    /**
     * Applied only by the `trail/clear` command ({@see TrailController::actionClear()}).
     */
    public int|false $trailLifetime = false;

    /**
     * Applied only by `user-login/clear` ({@see UserLoginController::actionClear()}); the rows are personal
     * data.
     */
    public int|false $userLoginLifetime = false;

    public bool $enableConsentLog = true;

    public int|false $consentLifetime = false;

    public $defaultRoute = 'dashboard';
    public $layout = 'main';

    /**
     * {@see getInstance()} answers `null` outside the module's own requests, and the error view renders outside.
     */
    public static function current(): self
    {
        /** @var self $module */
        $module = Yii::$app->getModule('admin');
        return $module;
    }

    /**
     * The account when the dashboard admits none of the user's roles, as after a signup.
     *
     * @return array<int|string, mixed>
     */
    public function getHomeRoute(): array
    {
        return $this->isDashboardAvailable() ? ['/admin/dashboard/index'] : ['/admin/account/update'];
    }

    public function isDashboardAvailable(): bool
    {
        $webuser = WebUser::current();
        $controller = $this->createController('dashboard')[0] ?? null;

        if (!$webuser || $webuser->getIsGuest() || !$controller instanceof DashboardController) {
            return false;
        }

        foreach ($controller->roles as $role) {
            if ($role === User::ROLE_AUTHENTICATED || $webuser->can($role)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function beforeAction($action): bool
    {
        $request = $action->controller->request;

        if ($request instanceof Request) {
            $this->assertAliasPath($request);

            //  Redirects draft URLs for the backend, but only if it's not an AJAX to prevent breaking frontend
            // implementations or REST APIs that use admin endpoints.
            // The request URL already carries a tenant's path, which `createAbsoluteUrl()` would prepend again.
            if ($request->isDraftRequest() && !$request->getIsAjax()) {
                $url = Yii::$app->getUrlManager()->getHostInfo() . $request->getUrl();
                Application::current()->getResponse()->redirect($url);

                return false;
            }
        }

        $webuser = WebUser::current();

        if ($webuser) {
            $webuser->loginUrl ??= ['/admin/account/login'];

            if (!YII_DEBUG) {
                Application::current()->getErrorHandler()->errorView = self::ERROR_VIEW;
            }
        }

        $this->setLanguage();

        return parent::beforeAction($action);
    }

    /**
     * A path no URL rule matched becomes the route itself, which would keep the default `admin/…` serving beside
     * the path `params['adminAlias']` names.
     */
    protected function assertAliasPath(Request $request): void
    {
        $alias = Yii::$app->getAdminAlias();

        if ($alias !== 'admin' && str_starts_with(trim($request->getPathInfo(), '/') . '/', 'admin/')) {
            throw new NotFoundHttpException();
        }
    }

    protected function setLanguage(): void
    {
        $identity = WebUser::current()?->getIdentity();
        $language = $this->getSessionLanguage() ?? $identity?->language;

        Yii::$app->language = $language && $this->hasLanguage($language)
            ? $language
            : $this->getOfferedLanguage(Yii::$app->language);
    }

    /**
     * The URL manager resolves a content language, and an account may keep one the admin no longer offers.
     */
    public function getOfferedLanguage(?string $language): string
    {
        $languages = $this->getLanguages();
        return $language !== null && $this->hasLanguage($language) ? $language : (string)reset($languages);
    }

    /**
     * @return string[]
     */
    public function getLanguages(): array
    {
        return $this->languages ??= array_values(array_unique(Yii::$app->getI18n()->getLanguages()));
    }

    public function hasLanguage(string $language): bool
    {
        return in_array($language, $this->getLanguages(), true);
    }

    /**
     * `null` also when the picked one is no longer offered or the application has no session.
     */
    public function getSessionLanguage(): ?string
    {
        $language = $this->getSession()?->get($this->languageSessionKey);
        return is_string($language) && $this->hasLanguage($language) ? $language : null;
    }

    public function setSessionLanguage(?string $language): void
    {
        $session = $this->getSession();

        if ($language === null) {
            $session?->remove($this->languageSessionKey);
            return;
        }

        $session?->set($this->languageSessionKey, $language);
    }

    /**
     * `null` leaves it to the CSS, since no request can answer `prefers-color-scheme` (the client hint is
     * Chromium-only).
     */
    public function getColorScheme(): ?string
    {
        return WebUser::current()?->getIdentity()?->getColorScheme();
    }

    /**
     * Read off `$_COOKIE`, since {@see Request::getCookies()} silently skips the unsigned cookie the script writes.
     */
    public function isAsideCollapsed(): bool
    {
        return ($_COOKIE[$this->asideCookieName] ?? null) === self::ASIDE_COLLAPSED;
    }

    /**
     * Built with `new`, since the container's {@see Cookie} carries a `Domain` and a scoped twin hides this host-only
     * one in `$_COOKIE` (#195).
     */
    public function getAsideCookie(): Cookie
    {
        $cookie = new Cookie();
        $cookie->name = $this->asideCookieName;
        $cookie->httpOnly = false;
        $cookie->sameSite = Cookie::SAME_SITE_LAX;
        $cookie->secure = $this->asideCookieSecure
            ?? Application::current()->getRequest()->getIsSecureConnection();

        return $cookie;
    }

    private function getSession(): ?Session
    {
        /** @var Session|null $session */
        $session = Yii::$app->has('session') ? Yii::$app->get('session') : null;
        return $session;
    }

    public function dashboard(Dashboard $dashboard): Dashboard
    {
        $dashboard->addItem(
            DashboardItem::make()
                ->icon('user-plus')
                ->label(Yii::t('skeleton', 'USER_CREATE_BUTTON'))
                ->order(60)
                ->roles([User::AUTH_USER])
                ->url(['/admin/user/create']),
            DashboardItem::make()
                ->icon('globe')
                ->label(Yii::t('skeleton', 'NAV_HOMEPAGE_LABEL'))
                ->link(fn (A $link) => $link->target('_blank'))
                ->order(100)
                ->url('/'),
        );

        foreach ($this->getSubmodules() as $module) {
            if ($module instanceof ModuleInterface) {
                $dashboard = $module->dashboard($dashboard);
            }
        }

        return $dashboard;
    }

    /**
     * @param array<string, AuthItem> $items
     * @return array<string, AuthItem>
     */
    public function sortAuthItems(array $items): array
    {
        $roles = array_filter($items, fn (AuthItem $item): bool => $item->isRole());
        return $this->sortRoles($roles) + $items;
    }

    /**
     * @param array<string, AuthItem> $roles
     * @return array<string, AuthItem>
     */
    public function sortRoles(array $roles): array
    {
        ksort($roles);

        $sorted = [];

        foreach ([User::AUTH_ROLE_ADMIN, User::AUTH_ROLE_MANAGER] as $name) {
            if (isset($roles[$name])) {
                $sorted[$name] = $roles[$name];
            }
        }

        return $sorted + $roles;
    }

    /**
     * @return \yii\base\Module[]
     */
    public function getSubmodules(): array
    {
        $submodules = [];

        foreach (array_keys($this->getModules()) as $moduleName) {
            $submodules[] = $this->getModule($moduleName);
        }

        return $submodules;
    }
}
