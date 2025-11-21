<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\controllers\traits;

use Override;
use Yii;
use yii\helpers\Json;
use yii\web\Response;

trait HtmxControllerTrait
{
    #[Override]
    public function redirect($url, $statusCode = 302): Response
    {
        $response = parent::redirect($url, $statusCode);

        if ($this->isHtmxRequest()) {
            $target = $this->getHtmxRedirectTarget();

            if ($target !== false) {
                $headers = $this->response->getHeaders();
                $location = $headers->get('Location');

                if ($location) {
                    $headers->remove('Location');
                    $headers->set('HX-Location', Json::encode([
                        'path' => $location,
                        'target' => $target,
                    ]));
                }

                $this->response->setStatusCode(200);
            }
        }

        return $response;
    }

    #[Override]
    public function refresh($anchor = ''): Response
    {
        return $this->redirect($this->request->getUrl() . $anchor);
    }

    public function goBack($defaultUrl = null): Response
    {
        return $this->response->redirect(Yii::$app->getUser()->getReturnUrl($defaultUrl));
    }

    protected function requestHtmxRefresh(): static
    {
        if ($this->isHtmxRequest()) {
            $this->response->getHeaders()->set('HX-Refresh', 'true');
        }
    }

    protected function getHtmxRedirectTarget(): string|false
    {
        return '#wrap';
    }

    protected function isHtmxRequest(): bool
    {
        return $this->request->isHtmxRequest();
    }
}