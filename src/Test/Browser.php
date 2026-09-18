<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Exception;
use Hirtz\Skeleton\Web\Application;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Yii;
use yii\base\ExitException;
use yii\base\UserException;

/**
 * @extends AbstractBrowser<Request,Response>
 */
class Browser extends AbstractBrowser
{
    /**
     * `$_COOKIE`, `$_GET`, `$_POST` and `$_REQUEST` are replaced per request and `$_SERVER` has to be too, or a
     * header one request sends — an `HTTP_HX_REQUEST` — is sent by every request after it, in this browser and
     * in the next one the test opens. The snapshot is process-wide because `$_SERVER` is: what it holds before
     * the first request is PHPUnit's own environment, which no request may change.
     *
     * Named for the superglobal rather than after {@see AbstractBrowser::$server}, the browser's own default
     * bag, which a property of that name would redeclare as static — a fatal at class load that PHPUnit reports
     * only as "Premature end of PHP process".
     *
     * @var array<string, mixed>|null
     */
    private static ?array $superglobal = null;

    protected function doRequest(object $request): Response
    {
        $_SERVER = self::$superglobal ??= $_SERVER;

        $content = $request->getContent();
        $uri = $request->getUri();
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);

        $_SERVER['REQUEST_URI'] = null !== $query
            ? "$path?$query"
            : $path;

        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        $_SERVER['QUERY_STRING'] = (string)$query;

        $_COOKIE = $request->getCookies();
        $_REQUEST = $this->normalizeQueryParameters($request->getParameters());

        if (strtoupper($request->getMethod()) === 'GET') {
            $_GET = $_REQUEST;
            $_POST = [];
        } else {
            $_POST = $_REQUEST;
            $_GET = [];
        }

        $_SERVER = [
            ...$_SERVER,
            ...$request->getServer(),
        ];

        parse_str($query ?: '', $params);

        foreach ($params as $k => $v) {
            $_GET[$k] = $v;
        }

        Yii::$app->set('request', [
            ...Yii::$app->getComponents()['request'],
            'rawBody' => $content,
        ]);

        ob_start();

        try {
            Yii::$app->run();
        } catch (Exception $exception) {
            if ($exception instanceof UserException) {
                Yii::$app->getErrorHandler()->discardExistingOutput = false;
                Yii::$app->getErrorHandler()->handleException($exception);
            } elseif (!$exception instanceof ExitException) {
                throw $exception;
            }
        }

        $response = Application::current()->getResponse();

        if (Application::current()->getRequest()->enableCookieValidation) {
            $validationKey = Application::current()->getRequest()->cookieValidationKey;
        }

        /** @var \yii\web\Cookie $cookie */
        foreach ($response->getCookies() as $cookie) {
            $value = $cookie->value;

            if (1 !== $cookie->expire && isset($validationKey)) {
                $data = [$cookie->name, $cookie->value];
                $value = Yii::$app->getSecurity()->hashData(serialize($data), $validationKey);
            }

            $expires = is_int($cookie->expire) ? (string)$cookie->expire : null;

            $this->getCookieJar()->set(new Cookie(
                $cookie->name,
                $value,
                $expires,
                $cookie->path,
                $cookie->domain,
                $cookie->secure,
                $cookie->httpOnly,
            ));
        }

        $content = ob_get_clean() ?: '';

        $status = $response->getStatusCode();
        $headers = $response->getHeaders()->toArray();

        $response->clear();

        Yii::$app->set('view', Yii::$app->getComponents()['view']);

        return new Response($content, $status, $headers);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<int|string, array<mixed>|string>
     */
    private function normalizeQueryParameters(array $parameters): array
    {
        parse_str(http_build_query($parameters), $normalizedParameters);
        return $normalizedParameters;
    }
}
