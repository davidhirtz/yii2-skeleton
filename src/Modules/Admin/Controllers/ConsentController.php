<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Consent;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
class ConsentController extends Controller
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
                        'roles' => [Module::AUTH_SYSTEM],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?string $q = null): Response|string
    {
        if (!$this->module->enableConsentLog) {
            throw new NotFoundHttpException();
        }

        $query = Consent::find()->orderBy(['id' => SORT_DESC]);

        if ($q) {
            $query->andWhere(['uuid' => trim($q)]);
        }

        $provider = new ActiveDataProvider([
            'sort' => false,
            'query' => $query,
            'pagination' => ['defaultPageSize' => 50],
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }
}
