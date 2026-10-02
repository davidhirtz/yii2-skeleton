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

    /**
     * @var bool whether the redirect table should be checked first on 404 errors, defaults to `true`.
     */
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
     * A {@see Redirect} may name a host (`www.example.com/old`) or not (`old`); a host-qualified record wins. The
     * host is the URL manager's, not the request's, so a canonical host set by a URL manager subclass matches too.
     * A record naming the query string wins over one naming the path alone, which is the one a tracked link
     * (`old?utm_source=…`) finds, and which hands the query on to the target.
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
