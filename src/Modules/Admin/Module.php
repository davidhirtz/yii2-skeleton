<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use Override;
use Yii;

class Module extends \Hirtz\Skeleton\Base\Module
{
    public string $alias = 'admin';

    /**
     * @var bool whether the fulltext search is available: the navbar button, both search actions, the index writes
     * of {@see \Hirtz\Skeleton\Behaviors\SearchBehavior} and the `search` console commands.
     */
    public bool $enableSearch = true;

    public ?int $trailLifetime = null;

    /**
     * @var int|null how long a `user_login` record is kept, in seconds. The table holds an IP address and a user
     * agent for every login, so it is personal data with no reason to be kept forever — set it and run
     * `user-login/clear` from cron.
     */
    public ?int $userLoginLifetime = null;

    public $defaultRoute = 'dashboard';
    public $layout = 'main';

    #[Override]
    public function beforeAction($action): bool
    {
        $request = $action->controller->request;

        if ($request instanceof Request) {
            //  Redirects draft URLs for the backend, but only if it's not an AJAX to prevent breaking frontend
            // implementations or REST APIs that use admin endpoints.
            if ($request->isDraftRequest() && !$request->getIsAjax()) {
                $url = Yii::$app->getUrlManager()->createAbsoluteUrl($request->getUrl());
                Yii::$app->getResponse()->redirect($url)->send();
            }
        }

        if (Yii::$app->has('user')) {
            Yii::$app->getUser()->loginUrl ??= ['/admin/account/login'];

            if (!YII_DEBUG) {
                Yii::$app->getErrorHandler()->errorView = '@skeleton/../resources/views/admin/views/dashboard/error.php';
            }
        }

        $this->setLanguage($request instanceof Request ? $request : null);

        return parent::beforeAction($action);
    }

    /**
     * The language picked via {@see Widgets\Buttons\LanguageDropdownButton} is kept in the session, so it outlives
     * the request that set it — the login page included, where there is no account to fall back to yet and the
     * language the URL manager resolved stands. That language is the frontend's — with `UrlManager::$i18nUrl` the
     * path's, with a tenant its own — and never the admin's, which belongs to the account and not to the content
     * it edits.
     */
    protected function setLanguage(?Request $request): void
    {
        $i18n = Yii::$app->getI18n();
        $language = $request ? $request->getQueryParam($request->languageParam) : null;

        if (is_string($language) && $i18n->hasLanguage($language)) {
            $i18n->setSessionLanguage($language);
        }

        $identity = Yii::$app->has('user') ? Yii::$app->getUser()->getIdentity() : null;
        $language = $i18n->getSessionLanguage() ?? $identity?->language;

        if ($language) {
            Yii::$app->language = $language;
        }
    }

    public function dashboard(Dashboard $dashboard): Dashboard
    {
        $dashboard->addItem(
            DashboardItem::make()
                ->icon('user-plus')
                ->label(Yii::t('skeleton', 'MODULE_CREATE_NEW_USER'))
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
