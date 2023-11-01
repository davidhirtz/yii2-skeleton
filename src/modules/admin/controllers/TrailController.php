<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;

class TrailController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['trailIndex'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?int $id = null, ?string $model = null, ?int $user = null): Response|string
    {
        $model = $model ? explode('@', $model) : null;

        $provider = Yii::$container->get(TrailActiveDataProvider::class, [], [
            'user' => $user ? User::findOne($user) : null,
            'model' => $model[0] ?? null,
            'modelId' => $model[1] ?? null,
            'trailId' => $id,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }
}