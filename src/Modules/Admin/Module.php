<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\Behaviors\UserLanguageBehavior;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Config\Config;
use Hirtz\Skeleton\Modules\Admin\Config\DashboardItem;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\DashboardNavItem;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SystemNavItem;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserNavItem;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\DashboardPanel;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Override;
use Yii;

class Module extends \Hirtz\Skeleton\Base\Module implements ModuleInterface
{
    public string $alias = 'admin';
    public ?int $trailLifetime = null;
    public bool $showInBreadcrumbs = true;

    public $defaultRoute = 'dashboard';
    public $layout = 'main';

    private array $dashboardPanels = [];

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

    /**
     * @return array<string, DashboardPanel>
     */
    public function getDashboardPanels(): array
    {
        $panels = $this->getDefaultDashboardPanels();

        foreach ($this->getSubmodules() as $module) {
            if ($module instanceof ModuleInterface) {
                foreach ($module->getDashboardPanels() as $key => $panel) {
                    $panels = Config::merge($panels, $key, $panel);
                }
            }
        }

        foreach ($this->dashboardPanels as $key => $panel) {
            $panels = Config::merge($panels, $key, $panel);
        }

        return array_filter($panels);
    }

    /**
     * @return array<string, DashboardPanel>
     */
    protected function getDefaultDashboardPanels(): array
    {
        return [
            'skeleton' => new DashboardPanel(
                name: Yii::t('skeleton', 'Administration'),
                items: [
                    'user' => new DashboardItem(
                        label: Yii::t('skeleton', 'Create New User'),
                        url: ['/admin/user/create'],
                        icon: 'user-plus',
                        roles: [User::AUTH_USER_CREATE],
                    ),
                    'account' => new DashboardItem(
                        label: Yii::t('skeleton', 'Your Account'),
                        url: ['/admin/account/update'],
                        icon: 'user',
                    ),
                    'system' => new DashboardItem(
                        label: Yii::t('skeleton', 'System Settings'),
                        url: ['/admin/system/index'],
                        icon: 'cog',
                        roles: [User::AUTH_ROLE_ADMIN],
                    ),
                    'trail' => new DashboardItem(
                        label: Yii::t('skeleton', 'History'),
                        url: ['/admin/trail/index'],
                        icon: 'history',
                        roles: [Trail::AUTH_TRAIL_INDEX],
                    ),
                    'redirect' => new DashboardItem(
                        label: Yii::t('skeleton', 'Redirects'),
                        url: ['/admin/redirect/index'],
                        icon: 'forward',
                        roles: [Redirect::AUTH_REDIRECT_CREATE],
                    ),
                    'homepage' => new DashboardItem(
                        label: Yii::t('skeleton', 'Homepage'),
                        url: '/',
                        icon: 'globe',
                        attributes: ['target' => '_blank'],
                    ),
                ]
            ),
        ];
    }

    /**
     * @param array<DashboardItem|null> $panels
     */
    public function setDashboardPanels(array $panels = []): void
    {
        $this->dashboardPanels = $panels;
    }

    public function getName(): string
    {
        return Yii::t('skeleton', 'Admin');
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
