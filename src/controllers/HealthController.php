<?php

namespace davidhirtz\yii2\skeleton\controllers;

use davidhirtz\yii2\skeleton\web\Controller;
use Exception;
use Yii;

/**
 * Class HealthController
 * @package davidhirtz\yii2\skeleton\controllers
 */
class HealthController extends Controller
{
    /**
     * @var bool
     */
    public $enableCsrfValidation = false;

    
    public function actionIndex()
    {
        try {
            Yii::$app->getDb()->open();
        } catch (Exception $exception) {
            Yii::error($exception);
            Yii::$app->getResponse()->setStatusCode(503);
        }
    }
}
