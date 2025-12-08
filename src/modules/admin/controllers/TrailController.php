<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;

class TrailController extends Controller
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
