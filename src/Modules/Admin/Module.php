<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\Console\Controllers\TrailController;
use Hirtz\Skeleton\Console\Controllers\UserLoginController;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Web\User as WebUser;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use Override;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Session;

class Module extends \Hirtz\Skeleton\Base\Module
{
    final public const string AUTH_SYSTEM = 'system';

    /**
     * Only a production request reaches it, so nothing but {@see \Hirtz\Skeleton\Tests\Modules\Admin\ModuleTest}
     * notices the day the view moves.
     */
    final public const string ERROR_VIEW = '@skeleton/../resources/views/admin/dashboard/error.php';

    /**
     * @var bool whether the fulltext search is available: the navbar button, both search actions, the index writes
     * of {@see \Hirtz\Skeleton\Behaviors\SearchBehavior} and the `search` console commands.
     */
    public bool $enableSearch = true;

    /**
     * @var string[]|null the languages the admin interface is offered in, defaulting to the application's content
     * languages. The two are only related by that default: the admin overrides whichever language the URL manager
     * resolved, so a project whose content languages the admin has no translation for names its own list here.
     */
    public ?array $languages = null;

    /**
     * @var string the session key holding the language picked for the current session, see
     * {@see Module::getSessionLanguage()}.
     */
    public string $languageSessionKey = 'language';

    /**
     * @var int|false how long a `trail` record is kept, in seconds. Set it and run `trail/clear` console command.
     * {@see TrailController::actionClear()}
     */
    public int|false $trailLifetime = false;

    /**
     * @var int|false how long a `user_login` record is kept, in seconds. The table holds an IP address and a user
     * agent for every login, so it is personal data with no reason to be kept forever — set it and run
     * `user-login/clear` console command.
     * {@see UserLoginController::actionClear()}
     */
    public int|false $userLoginLifetime = false;

    public $defaultRoute = 'dashboard';
    public $layout = 'main';

    #[Override]
    public function beforeAction($action): bool
    {
        $request = $action->controller->request;

        if ($request instanceof Request) {
            $this->assertAliasPath($request);

            //  Redirects draft URLs for the backend, but only if it's not an AJAX to prevent breaking frontend
            // implementations or REST APIs that use admin endpoints.
            if ($request->isDraftRequest() && !$request->getIsAjax()) {
                $url = Yii::$app->getUrlManager()->createAbsoluteUrl($request->getUrl());
                Application::current()->getResponse()->redirect($url)->send();
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

    /**
     * The language {@see Controllers\AccountController::actionLanguage()} wrote is kept in the session, so it
     * outlives the request that set it — the login page included, where there is no account to fall back to yet and
     * the language the URL manager resolved stands. That language is the frontend's — with `UrlManager::$i18nUrl`
     * the path's, with a tenant its own — and never the admin's, which belongs to the account and not to the
     * content it edits.
     */
    protected function setLanguage(): void
    {
        $languages = $this->getLanguages();

        if (count($languages) === 1) {
            Yii::$app->language = reset($languages);
            return;
        }

        $identity = WebUser::current()?->getIdentity();
        $language = $this->getSessionLanguage() ?? $identity?->language;

        if ($language && $this->hasLanguage($language)) {
            Yii::$app->language = $language;
        }
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
     * The language picked for the current session, `null` when none was picked, the picked one is no longer
     * offered or the application has no session at all.
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
                ->label(Yii::t('skeleton', 'DASHBOARD_ACTION_USER_CREATE'))
                ->url(['/admin/user/create'])
                ->roles([User::AUTH_USER]),
            DashboardItem::make()
                ->icon('globe')
                ->label(Yii::t('skeleton', 'MODULE_OPEN_HOMEPAGE'))
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
