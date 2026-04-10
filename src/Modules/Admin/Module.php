<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\Behaviors\UserLanguageBehavior;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\DashboardNavItem;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SystemNavItem;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserNavItem;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use Override;
use Yii;

class Module extends \Hirtz\Skeleton\Base\Module implements ModuleInterface
{
    public string $alias = 'admin';
    public ?int $trailLifetime = null;
    public bool $showInBreadcrumbs = true;

    public $defaultRoute = 'dashboard';
    public $layout = 'main';

    #[Override]
    public function beforeAction($action): bool
    {
        $request = $action->controller->request;

        if (Yii::$app->has('user')) {
            Yii::$app->getUser()->loginUrl ??= ['/admin/account/login'];

            if (!YII_DEBUG) {
                Yii::$app->getErrorHandler()->errorView = '@skeleton/../resources/views/admin/views/dashboard/error.php';
            }
        }

        if ($request instanceof Request) {
            //  Redirects draft URLs for the backend, but only if it's not an AJAX to prevent breaking frontend
            // implementations or REST APIs that use admin endpoints.
            if ($request->isDraftRequest() && !$request->getIsAjax()) {
                $url = Yii::$app->getUrlManager()->createAbsoluteUrl($request->getUrl());
                $action->controller->response->redirect($url)->send();
            }

            if (count(Yii::$app->getI18n()->getLanguages()) > 1) {
                $action->controller->attachBehavior('UserLanguageBehavior', UserLanguageBehavior::class);
            }
        }

        return parent::beforeAction($action);
    }

    #[Override]
    public function aside(Nav $nav): Nav
    {
        $nav->addItems(DashboardNavItem::make(), UserNavItem::make(), SystemNavItem::make());

        foreach ($this->getSubmodules() as $module) {
            if ($module instanceof ModuleInterface) {
                $nav = $module->aside($nav);
            }
        }

        return $nav;
    }

    #[Override]
    public function dashboard(Dashboard $dashboard): Dashboard
    {
        $dashboard->addItems(
            DashboardItem::make()
                ->icon('user-plus')
                ->label(Yii::t('skeleton', 'Create New User'))
                ->url(['/admin/user/create'])
                ->roles([User::AUTH_USER_CREATE]),
            DashboardItem::make()
                ->icon('globe')
                ->label(Yii::t('skeleton', 'Open Homepage'))
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
