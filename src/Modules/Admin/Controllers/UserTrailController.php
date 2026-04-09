<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;

class UserTrailController extends Controller
{
    use UserTrait;

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
                        'actions' => ['index'],
                        'roles' => [Trail::AUTH_TRAIL_INDEX],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(int $id): Response|string
    {
        $user = $this->findUser($id, Trail::AUTH_TRAIL_INDEX);

        $provider = Yii::$container->get(TrailActiveDataProvider::class, config: [
            'user' => $user,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }
}
