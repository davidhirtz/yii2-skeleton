<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class LogController extends Controller
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
                        'actions' => ['delete', 'view'],
                        'roles' => [User::AUTH_ROLE_ADMIN],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
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
        $provider = Yii::$container->get(LogDataProvider::class, config: [
            'file' => $log,
        ]);

        if (!$provider->isFileValid()) {
            throw new NotFoundHttpException();
        }

        FileHelper::unlink($provider->file);

        return $this->redirect(['/admin/system/index']);
    }

    protected function getLogDataProvider(?string $file = null): LogDataProvider
    {
        return Yii::$container->get(LogDataProvider::class, config: [
            'file' => $file,
        ]);
    }
}