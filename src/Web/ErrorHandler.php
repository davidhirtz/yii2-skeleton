<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Models\Redirect;
use Override;
use Yii;
use yii\db\Expression;
use yii\web\HttpException;

class ErrorHandler extends \yii\web\ErrorHandler
{
    public $traceLine = '<a href="phpstorm://open?file={file}&line={line}">{html}</a>';

    public bool $enableRedirect = true;

    #[Override]
    protected function renderException($exception): void
    {
        if (
            $this->enableRedirect
            && $exception instanceof HttpException
            && $exception->statusCode === 404
            && $this->redirectRequestUri()
        ) {
            return;
        }

        parent::renderException($exception);
    }

    /**
     * A host-qualified record (the URL manager's host, not the request's) wins, then one naming the query string over
     * the path alone, which hands a tracked link's query on to the target.
     */
    protected function redirectRequestUri(): bool
    {
        $url = trim((string)Application::current()->getRequest()->getUrl(), '/');

        if (!$url) {
            return false;
        }

        $host = parse_url((string)Yii::$app->getUrlManager()->getHostInfo(), PHP_URL_HOST);
        $redirect = $this->findRedirectByRequestUri($host ? ["$host/$url", $url] : [$url]);
        $query = null;

        if (!$redirect && str_contains($url, '?')) {
            [$path, $query] = explode('?', $url, 2);
            $path = trim($path, '/');

            if ($path !== '') {
                $redirect = $this->findRedirectByRequestUri($host ? ["$host/$path", $path] : [$path]);
            }
        }

        if (!$redirect) {
            return false;
        }

        $target = $redirect->getBaseUrl() . $redirect->url;

        if ($query) {
            $target .= (str_contains($target, '?') ? '&' : '?') . $query;
        }

        $response = Application::current()->getResponse();
        $response->redirect($target, $redirect->type);
        $response->send();

        return true;
    }

    /**
     * @param list<string> $requestUris
     */
    protected function findRedirectByRequestUri(array $requestUris): ?Redirect
    {
        return Redirect::find()
            ->select(['type', 'url'])
            ->where(['request_uri' => $requestUris])
            ->orderBy(new Expression('LENGTH([[request_uri]]) DESC'))
            ->limit(1)
            ->one();
    }
}
