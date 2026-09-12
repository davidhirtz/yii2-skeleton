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

            $identity = Yii::$app->getUser()->getIdentity();

            if ($identity) {
                Yii::$app->language = $identity->language;
            }
        }


        return parent::beforeAction($action);
    }

    public function dashboard(Dashboard $dashboard): Dashboard
    {
        $dashboard->addItem(
            DashboardItem::make()
                ->icon('user-plus')
                ->label(Yii::t('skeleton', 'MODULE_CREATE_NEW_USER'))
                ->url(['/admin/user/create'])
                ->roles([User::AUTH_USER_CREATE]),
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
