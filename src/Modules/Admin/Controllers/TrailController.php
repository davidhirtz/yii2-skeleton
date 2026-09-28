<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Actions\RestoreTrail;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
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
                        'actions' => ['index', 'restore'],
                        'roles' => [Trail::AUTH_TRAIL_INDEX],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'restore' => ['post'],
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

    /**
     * Writes the values an update replaced back. Reading the trail is not enough: the acting user needs the
     * record's own permission, as for any other change to it.
     */
    public function actionRestore(int $id): Response
    {
        $trail = Trail::findOne($id);

        if (!$trail || !RestoreTrail::isRestorable($trail)) {
            throw new NotFoundHttpException();
        }

        $model = $trail->getModelRecord();

        if (!$model instanceof TrailModelInterface || !$this->webuser->can($model->getPermissionName())) {
            throw new ForbiddenHttpException();
        }

        $action = Yii::$container->get(RestoreTrail::class, [$trail]);

        if ($action->run()) {
            $this->success(Yii::t('skeleton', 'TRAIL_SUCCESS_RESTORED', ['model' => $model->getAdminName()]));
        } else {
            $this->error($action->getModel()?->getFirstErrors() ?: Yii::t('skeleton', 'TRAIL_ERROR_RESTORE'));
        }

        if ($skipped = $action->getSkipped()) {
            $this->warning(Yii::t('skeleton', 'TRAIL_WARNING_RESTORE_SKIPPED', [
                'attributes' => implode(', ', array_map($model->getAttributeLabel(...), $skipped)),
            ]));
        }

        $modelId = is_array($trail->model_id) ? implode('-', $trail->model_id) : (string)$trail->model_id;

        return $this->redirect(['index', 'model' => "$trail->model_class@$modelId"]);
    }
}
