<?php

namespace davidhirtz\yii2\skeleton\web;

/**
 * Class ErrorAction
 * @package davidhirtz\yii2\skeleton\web
 */
class ErrorAction extends \yii\web\ErrorAction
{
    /**
     * @inheritdoc
     */
    protected function renderAjaxResponse()
    {
        return $this->getExceptionMessage();
    }
}