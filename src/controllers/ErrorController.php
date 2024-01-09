<?php

namespace davidhirtz\yii2\skeleton\controllers;

use davidhirtz\yii2\skeleton\controllers\traits\AjaxRouteTrait;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;

/**
 * Simple error controller that uses the layout of the application.
 *
 * ```php
 * Yii::$app->getErrorHandler()->errorAction = 'error/index';
 * ```
 */
class ErrorController extends Controller
{
    use AjaxRouteTrait;

    public string $viewFile = 'error';

    public function actionIndex(): string
    {
        if (Yii::$app->getRequest()->getIsAjax() && !Yii::$app->getRequest()->getIsAjaxRoute()) {
            return $this->getAjaxErrorResponse();
        }

        return $this->render($this->viewFile, [
            'exception' => Yii::$app->getErrorHandler()->exception,
            'response' => Yii::$app->getResponse(),
            'email' => Yii::$app->params['email'],
        ]);
    }

    protected function getAjaxErrorResponse(): string
    {
        return Yii::$app->getErrorHandler()->exception->getMessage();
    }
}
