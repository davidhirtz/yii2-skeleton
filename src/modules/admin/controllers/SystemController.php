<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\controllers;

use Hirtz\Skeleton\caching\CacheComponents;
use Hirtz\Skeleton\helpers\FileHelper;
use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\web\Controller;
use Override;
use Yii;
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
        return $this->render('index');
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
}
