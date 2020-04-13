<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\web\Controller;
use yii\filters\AccessControl;

/**
 * Class SiteController.
 * @package app\controllers
 *
 * @property Module $module
 */
class DashboardController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'error'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => $this->module->roles,
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
        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'panels' => $this->module->panels,
        ]);
    }
}
