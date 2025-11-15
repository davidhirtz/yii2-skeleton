<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\caching\CacheComponents;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\models\Session;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Override;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Connection;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
                        'actions' => ['delete', 'flush', 'index', 'publish', 'session-gc', 'view'],
                        'roles' => [User::AUTH_ROLE_ADMIN],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'flush' => ['post'],
                    'publish' => ['post'],
                    'session-gc' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): Response|string
    {
        $sessions = new ArrayDataProvider([
            'allModels' => [
                [
                    'sessionCount' => Session::find()->count(),
                    'expiredSessionCount' => Session::find()
                        ->where(['<', 'expire', time()])
                        ->count(),
                ],
            ],
            'pagination' => false,
            'sort' => false,
        ]);

        return $this->render('index', [
            'logs' => $this->getLogDataProvider(),
            'sessions' => $sessions,
        ]);
    }

    /**
     * @noinspection PhpUnused
     */
    public function actionPublish(): Response|string
    {
        $basePath = Yii::$app->getAssetManager()->basePath;
        $assets = FileHelper::findDirectories($basePath, ['recursive' => false]);

        foreach ($assets as $asset) {
            FileHelper::removeDirectory($asset);
        }

        $this->success(Yii::t('skeleton', 'All assets refreshed.'));
        return $this->redirect(['index']);
    }

    public function actionFlush(string $cache): Response|string
    {
        if (!in_array($cache, array_keys(CacheComponents::getAll()), true)) {
            throw new NotFoundHttpException();
        }

        Yii::$app->get($cache)->flush();

        $this->success(Yii::t('skeleton', 'Cache was flushed.'));
        return $this->redirect(['index']);
    }

    /**
     * @noinspection PhpUnused
     */
    public function actionSchema(string $db): Response|string
    {
        $connection = Yii::$app->get($db, false);

        if (!$connection instanceof Connection) {
            throw new NotFoundHttpException();
        }

        $schema = $connection->getSchema();
        $schema->refresh();

        return $this->redirect(['index']);
    }

    public function actionSessionGc(): Response|string
    {
        Yii::$app->getSession()->gcSession(0);
        $this->success(Yii::t('skeleton', 'Expired sessions were deleted.'));
        return $this->redirect(['index']);
    }

    public function actionView(string $log, bool $raw = false): Response|string
    {
        $provider = $this->getLogDataProvider($log);

        if (!$provider->isFileValid()) {
            throw new NotFoundHttpException();
        }

        if ($raw) {
            return Yii::$app->getResponse()->sendFile($provider->file, basename($log), [
                'mimeType' => 'text/plain',
                'inline' => true,
            ]);
        }

        return $this->render('view', [
            'provider' => $provider,
        ]);
    }

    public function actionDelete(string $log): Response|string
    {
        $provider = $this->getLogDataProvider($log);

        if (!$provider->isFileValid()) {
            throw new NotFoundHttpException();
        }

        FileHelper::unlink($provider->file);
        return $this->redirect(['index']);
    }

    protected function getLogDataProvider(?string $file = null): LogDataProvider
    {
        return Yii::$container->get(LogDataProvider::class, [], [
            'file' => $file,
        ]);
    }
}
