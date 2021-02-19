<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;

/**
 * Class TrailController
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 */
class TrailController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
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

    /**
     * @param int $id
     * @param string $model
     * @param int $user
     * @return string
     */
    public function actionIndex($id = null, $model = null, $user = null)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $model = explode(':', $model);

        if ($module->trailLifetime > 0) {
            Trail::deleteAll(['<', 'created_at', (string)(new DateTime())->setTimestamp(time() - $module->trailLifetime)]);
        }

        $provider = new TrailActiveDataProvider([
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