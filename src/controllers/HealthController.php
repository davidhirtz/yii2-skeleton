<?php

namespace davidhirtz\yii2\skeleton\controllers;

use davidhirtz\yii2\skeleton\web\Controller;
use Exception;
use Yii;

class HealthController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex(): void
    {
        try {
            Yii::$app->getDb()->open();
        } catch (Exception $exception) {
            Yii::error($exception);
            Yii::$app->getResponse()->setStatusCode(503);
        }
    }
}
