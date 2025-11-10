<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\models\Session;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\caching\Cache;
use yii\data\ArrayDataProvider;
use yii\db\Connection;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SystemController extends Controller
{
    #[\Override]
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
        $assets = new ArrayDataProvider([
            'allModels' => $this->findAssets(),
            'pagination' => false,
            'sort' => false,
        ]);

        $caches = [];

        foreach ($this->findCaches() as $name => $class) {
            $caches[] = [
                'name' => $name,
                'class' => $class,
            ];
        }

        $caches = new ArrayDataProvider([
            'allModels' => $caches,
            'pagination' => false,
            'sort' => false,
        ]);

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
            'assets' => $assets,
            'caches' => $caches,
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
        if (!in_array($cache, array_keys($this->findCaches()), true)) {
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

    protected function findAssets(): array
    {
        $manager = Yii::$app->getAssetManager();
        $basePath = $manager->basePath;
        $baseUrl = $manager->baseUrl;

        $directories = FileHelper::findDirectories($basePath, ['recursive' => false]);
        $assets = [];

        foreach ($directories as $directory) {
            $handle = @opendir($directory);
            $basename = pathinfo((string)$directory, PATHINFO_BASENAME);
            $files = [];

            while (($file = readdir($handle)) !== false) {
                if ($file !== '.' && $file !== '..') {
                    $files[$file] = $baseUrl . '/' . $basename . '/';
                }
            }

            closedir($handle);

            $assets[] = [
                'name' => $basename,
                'files' => $files,
                'modified' => filemtime($directory),
            ];
        }

        return $assets;
    }

    protected function findCaches(): array
    {
        $caches = [];

        foreach (Yii::$app->getComponents() as $name => $component) {
            if ($component instanceof Cache) {
                $caches[$name] = $component::class;
            } elseif (is_array($component) && isset($component['class']) && $this->isCacheClass($component['class'])) {
                $caches[$name] = $component['class'];
            } elseif (is_string($component) && $this->isCacheClass($component)) {
                $caches[$name] = $component;
            }
        }

        ksort($caches);
        return $caches;
    }

    private function isCacheClass(string $className): bool
    {
        return is_subclass_of($className, Cache::class);
    }
}
