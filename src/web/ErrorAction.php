<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;
use yii\web\HttpException;

class ErrorAction extends \yii\web\ErrorAction
{
    public ?string $email = null;

    public function init(): void
    {
        $this->email ??= Yii::$app->params['email'];
        $this->view ??= '@skeleton/views/error';

        parent::init();
    }

    public function run(): string
    {
        if ($this->layout !== null) {
            $this->controller->layout = $this->layout;
        }

        Yii::$app->getResponse()->setStatusCodeByException($this->exception);

        if (Yii::$app->getRequest()->getIsAjax() && !Yii::$app->getRequest()->getIsAjaxRoute()) {
            return $this->renderAjaxResponse();
        }

        return $this->renderHtmlResponse();
    }

    protected function renderAjaxResponse(): string
    {
        return $this->getExceptionMessage();
    }

    protected function getExceptionName(): string
    {
        return match ($code = $this->getExceptionCode()) {
            403, 404 => Yii::t('yii', 'Error') . " $code",
            default => parent::getExceptionMessage(),
        };
    }

    protected function getExceptionMessage(): string
    {
        $code = $this->exception instanceof HttpException ? $this->exception->statusCode : null;

        return match ($this->getExceptionCode()) {
            403 => Yii::t('skeleton', 'Permission denied'),
            404 => Yii::t('skeleton', 'The requested page was not found'),
            default => parent::getExceptionMessage(),
        };
    }

    protected function getViewRenderParams(): array
    {
        return [
            ...parent::getViewRenderParams(),
            'name' => $this->getExceptionName(),
            'message' => $this->getExceptionMessage(),
            'exception' => $this->exception,
            'email' => $this->email,
        ];
    }
}
