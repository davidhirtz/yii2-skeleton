<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;
use Hirtz\Skeleton\Modules\Admin\Module;

/**
 * @extends Controller<Module>
 */
class TrailController extends Controller
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
                        'actions' => ['index'],
                        'roles' => [Trail::AUTH_TRAIL_INDEX],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?int $id = null, ?string $model = null): Response|string
    {
        $model = $model ? explode('@', $model) : null;

        $provider = Yii::$container->get(TrailActiveDataProvider::class, config: [
            'model' => $model[0] ?? null,
            'modelId' => $model[1] ?? null,
            'trailId' => $id,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }
}
