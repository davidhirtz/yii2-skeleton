<?php

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

/**
 * Class SystemController
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 */
class SystemController extends Controller
{
    /***********************************************************************
     * Behaviors.
     ***********************************************************************/

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
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

    /***********************************************************************
     * Actions.
     ***********************************************************************/

    /**
     * @return string
     */
    public function actionIndex()
    {
        // Assets.
        $assets = new ArrayDataProvider([
            'allModels' => $this->findAssets(),
            'pagination' => false,
            'sort' => false,
        ]);

        // Caches.
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

        // Sessions.
        $sessionCount = Session::find()->count();
        $expiredSessionCount = Session::find()
            ->where(['<', 'expire', time()])
            ->count();

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'assets' => $assets,
            'caches' => $caches,
            'logs' => $this->getLogDataProvider(),
            'sessionCount' => $sessionCount,
            'expiredSessionCount' => $expiredSessionCount,
        ]);
    }

    /**
     * @return Response
     */
    public function actionPublish()
    {
        $basePath = Yii::$app->getAssetManager()->basePath;
        $assets = FileHelper::findDirectories($basePath, ['recursive' => false]);

        foreach ($assets as $asset) {
            FileHelper::removeDirectory($asset);
        }

        $this->success(Yii::t('skeleton', 'All assets refreshed.'));
        return $this->redirect(['index']);
    }

    /**
     * @param string $cache
     * @return Response
     */
    public function actionFlush($cache)
    {
        if (!in_array($cache, array_keys($this->findCaches()))) {
            throw new NotFoundHttpException();
        }

        Yii::$app->get($cache)->flush();

        $this->success(Yii::t('skeleton', 'Cache was flushed.'));
        return $this->redirect(['index']);
    }

    /**
     * @param string $db
     * @return Response
     */
    public function actionSchema($db)
    {
        $connection = Yii::$app->get($db, false);

        if (!$connection instanceof Connection) {
            throw new NotFoundHttpException();
        }

        $schema = $connection->getSchema();
        $schema->refresh();

        return $this->redirect(['index']);
    }

    /**
     * @return Response
     */
    public function actionSessionGc()
    {
        Yii::$app->getSession()->gcSession(0);
        $this->success(Yii::t('skeleton', 'Expired sessions were deleted.'));
        return $this->redirect(['index']);
    }

    /**
     * @param string $log
     * @param bool $raw
     * @return Response|string
     */
    public function actionView($log, $raw = false)
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

    /**
     * @param string $log
     * @return Response
     */
    public function actionDelete($log)
    {
        $provider = $this->getLogDataProvider($log);

        if (!$provider->isFileValid()) {
            throw new NotFoundHttpException();
        }

        FileHelper::removeFile($provider->file);
        return $this->redirect(['index']);
    }

    /**
     * @param string|null $file
     * @return LogDataProvider
     */
    protected function getLogDataProvider($file = null)
    {
        return $provider = Yii::createObject([
            'class' => LogDataProvider::class,
            'file' => $file,
        ]);
    }

    /**
     * @return array
     */
    protected function findAssets()
    {
        $manager = Yii::$app->getAssetManager();
        $basePath = $manager->basePath;
        $baseUrl = $manager->baseUrl;

        $directories = FileHelper::findDirectories($basePath, ['recursive' => false]);
        $assets = [];

        foreach ($directories as $directory) {
            $handle = @opendir($directory);
            $basename = pathinfo($directory, PATHINFO_BASENAME);
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

    /**
     * @return array
     */
    protected function findCaches()
    {
        $caches = [];

        foreach (Yii::$app->getComponents() as $name => $component) {
            if ($component instanceof Cache) {
                $caches[$name] = get_class($component);
            } elseif (is_array($component) && isset($component['class']) && $this->isCacheClass($component['class'])) {
                $caches[$name] = $component['class'];
            } elseif (is_string($component) && $this->isCacheClass($component)) {
                $caches[$name] = $component;
            }
        }

        ksort($caches);
        return $caches;
    }

    /**
     * Checks if given class is a Cache class.
     * @param string $className class name.
     * @return bool
     */
    private function isCacheClass($className)
    {
        return is_subclass_of($className, Cache::class);
    }
}