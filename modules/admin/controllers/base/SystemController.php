<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers\base;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\caching\Cache;
use yii\data\ArrayDataProvider;
use yii\db\Connection;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

/**
 * Class SystemController.
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
                        'actions' => ['delete', 'flush', 'index', 'publish', 'view'],
                        'roles' => ['admin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'flush' => ['post'],
                    'publish' => ['post'],
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

        // Logs.
        $logs = new ArrayDataProvider([
            'allModels' => $this->findLogs(),
            'pagination' => false,
            'sort' => false,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'assets' => $assets,
            'caches' => $caches,
            'logs' => $logs,
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionPublish()
    {
        $basePath = Yii::$app->getAssetManager()->basePath;
        $assets = FileHelper::findDirectories($basePath);

        foreach ($assets as $asset) {
            FileHelper::removeDirectory($asset);
        }

        $this->success(Yii::t('skeleton', 'All assets refreshed.'));
        return $this->redirect(['index']);
    }

    /**
     * @param string $cache
     * @return \yii\web\Response
     */
    public function actionFlush($cache)
    {
        if (!in_array($cache, array_keys($this->findCaches()))) {
            throw new NotFoundHttpException;
        }

        Yii::$app->get($cache)->flush();

        $this->success(Yii::t('skeleton', 'Cache was flushed.'));
        return $this->redirect(['index']);
    }

    /**
     * @param string $db
     * @return \yii\web\Response
     */
    public function actionSchema($db)
    {
        $connection = Yii::$app->get($db, false);

        if (!$connection instanceof Connection) {
            throw new NotFoundHttpException;
        }

        $schema = $connection->getSchema();
        $schema->refresh();

        return $this->redirect(['index']);
    }

    /**
     * @param string $log
     * @return \yii\web\Response
     */
    public function actionView($log)
    {
        if (array_key_exists($log, $this->findLogs())) {
            return Yii::$app->getResponse()->sendFile(Yii::getAlias('@app/runtime/logs/' . $log), basename($log), [
                'mimeType' => 'text/plain',
                'inline' => true,
            ]);
        }

        throw new NotFoundHttpException;
    }

    /**
     * @param string $log
     * @return \yii\web\Response
     */
    public function actionDelete($log)
    {
        if (array_key_exists($log, $this->findLogs())) {
            FileHelper::removeFile(Yii::getAlias('@app/runtime/logs/' . $log));
            return $this->redirect(['index']);
        }

        throw new NotFoundHttpException;
    }

    /***********************************************************************
     * Methods.
     ***********************************************************************/

    /**
     * @return array
     */
    protected function findAssets()
    {
        $assets = [];
        $manager = Yii::$app->getAssetManager();

        foreach ($this->findAssetFolders(Yii::getAlias('@app')) as $file) {
            $asset = $manager->getPublishedUrl($file);

            $assets[] = [
                'name' => pathinfo($file, PATHINFO_FILENAME),
                'directory' => $asset,
            ];
        }

        krsort($assets);
        return $assets;
    }

    /**
     * @param string $path
     * @return array
     */
    private function findAssetFolders($path)
    {
        $assets = "{$path}/assets";
        $files = is_dir($assets) ? FileHelper::findDirectories($assets) : [];

        if (is_dir($modules = Yii::getAlias("{$path}/modules"))) {
            foreach (FileHelper::findDirectories($modules) as $path) {
                $files = array_merge($files, $this->findAssetFolders($path));
            }
        }

        return $files;
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

    /**
     * @return array
     */
    protected function findLogs()
    {
        $logs = [];

        foreach (glob(Yii::getAlias('@app/runtime/logs/*')) as $file) {
            $logs[pathinfo($file, PATHINFO_BASENAME)] = filemtime($file);
        }

        return $logs;
    }
}