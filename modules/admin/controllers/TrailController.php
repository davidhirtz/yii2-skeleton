<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use yii\filters\AccessControl;

/**
 * Class TrailController
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 */
class TrailController extends Controller
{
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
                        'actions' => ['index'],
                        'roles' => ['admin'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $provider = new TrailActiveDataProvider();

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }
}