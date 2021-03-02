<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\models\Redirect;
use Yii;
use yii\web\HttpException;

/**
 * Class ErrorHandler
 * @package davidhirtz\yii2\skeleton\web
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @inheritDoc
     */
    public $traceLine = '<a href="phpstorm://open?file={file}&line={line}">{html}</a>';

    /**
     * @var bool whether the redirect table should be checked first on 404 errors, defaults to `true`.
     */
    public $enableRedirect = true;

    /**
     * @inheritDoc
     */
    protected function renderException($exception)
    {
        if ($this->enableRedirect && $exception instanceof HttpException && $exception->statusCode == 404) {
            $this->checkRedirectRequestUri();
        }

        parent::renderException($exception);
    }

    /**
     * Exits application and redirects to target url if a matching {@link Redirect} record was found.
     */
    protected function checkRedirectRequestUri()
    {
        if ($url = trim(Yii::$app->getRequest()->getUrl(), '/')) {
            if ($redirect = $this->findRedirectByRequestUri($url)) {
                Yii::$app->getResponse()->redirect($redirect->getBaseUrl() . $redirect->url, $redirect->type);
                Yii::$app->end();
            }
        }
    }

    /**
     * @param string $url
     * @return Redirect|null
     */
    protected function findRedirectByRequestUri($url)
    {
        return Redirect::find()
            ->select(['type', 'url'])
            ->where(['request_uri' => $url])
            ->limit(1)
            ->one();
    }
}