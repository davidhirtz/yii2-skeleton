<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Caching\CacheComponents;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\db\Connection;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
class SystemController extends Controller
{
    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['php-info'],
                        'roles' => [Module::AUTH_SYSTEM],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['flush', 'index', 'maintenance', 'publish', 'schema', 'server', 'session-gc'],
                        'roles' => [User::AUTH_ROLE_ADMIN, User::AUTH_ROLE_MANAGER],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'flush' => ['post'],
                    'publish' => ['post'],
                    'schema' => ['post'],
                    'session-gc' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): Response|string
    {
        return $this->render('index');
    }

    public function actionServer(): Response|string
    {
        return $this->render('server');
    }

    public function actionMaintenance(): Response|string
    {
        return $this->render('maintenance');
    }

    public function actionPhpInfo(): string
    {
        ob_start();
        phpinfo();

        $this->layout = false;
        return $this->renderContent((string)ob_get_clean());
    }

    public function actionPublish(): Response|string
    {
        $basePath = Yii::$app->getAssetManager()->basePath;
        $assets = FileHelper::findDirectories($basePath, ['recursive' => false]);

        foreach ($assets as $asset) {
            FileHelper::removeDirectory($asset);
        }

        $this->success(Yii::t('skeleton', 'SYSTEM_SUCCESS_REFRESHED'));
        return $this->redirect(['maintenance']);
    }

    public function actionFlush(string $cache): Response|string
    {
        if (!in_array($cache, array_keys(CacheComponents::getAll()), true)) {
            throw new NotFoundHttpException();
        }

        Yii::$app->get($cache)->flush();

        $this->success(Yii::t('skeleton', 'SYSTEM_SUCCESS_FLUSHED'));
        return $this->redirect(['maintenance']);
    }

    public function actionSchema(string $db): Response|string
    {
        $connection = Yii::$app->get($db, false);

        if (!$connection instanceof Connection) {
            throw new NotFoundHttpException();
        }

        $connection->getSchema()->refresh();

        $this->success(Yii::t('skeleton', 'SYSTEM_SUCCESS_REFRESHED_SCHEMA'));
        return $this->redirect(['maintenance']);
    }

    public function actionSessionGc(): Response|string
    {
        Application::current()->getSession()->gcSession(0);
        $this->success(Yii::t('skeleton', 'SYSTEM_SUCCESS_DELETED'));
        return $this->redirect(['maintenance']);
    }
}
