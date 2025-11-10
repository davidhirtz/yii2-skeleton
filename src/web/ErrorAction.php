<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use Override;
use Yii;

/**
 * @property Controller $controller
 */
class ErrorAction extends \yii\web\ErrorAction
{
    public ?string $email = null;

    #[Override]
    public function init(): void
    {
        $this->email ??= Yii::$app->params['email'];
        $this->view ??= '@skeleton/views/error';

        parent::init();
    }

    #[Override]
    public function run(): string
    {
        if ($this->layout !== null) {
            $this->controller->layout = $this->layout;
        }

        $this->controller->response->setStatusCodeByException($this->exception);

        if ($this->controller->request->getIsAjax() && !$this->controller->request->getIsAjaxRoute()) {
            return $this->renderAjaxResponse();
        }

        return $this->renderHtmlResponse();
    }

    #[Override]
    protected function renderAjaxResponse(): string
    {
        return $this->getExceptionMessage();
    }

    #[Override]
    protected function getExceptionName(): string
    {
        return match ($code = $this->getExceptionCode()) {
            403, 404 => Yii::t('yii', 'Error') . " $code",
            default => parent::getExceptionMessage(),
        };
    }

    #[Override]
    protected function getExceptionMessage(): string
    {
        return parent::getExceptionMessage() ?: match ($this->getExceptionCode()) {
            403 => Yii::t('skeleton', 'Permission denied.'),
            404 => Yii::t('yii', 'Page not found.'),
            default => Yii::t('yii', 'Error'),
        };
    }

    #[Override]
    protected function getViewRenderParams(): array
    {
        return [
            ...parent::getViewRenderParams(),
            'email' => $this->email,
        ];
    }
}
