<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HomePanelInterface;
use davidhirtz\yii2\skeleton\web\Controller;
use yii\filters\AccessControl;
use Yii;

/**
 * Class SiteController.
 * @package app\controllers
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
                        'roles' => ['@'],
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
        /** @var Module $module */
        $module = $this->module;

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'panels' => $module->panels,
        ]);
    }
}
