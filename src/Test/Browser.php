<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Exception;
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
    protected function doRequest(object $request): Response
    {
        $content = $request->getContent();
        $uri = $request->getUri();
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);

        $_SERVER['REQUEST_URI'] = null !== $query
            ? "$path?$query"
            : $path;

        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        $_SERVER['QUERY_STRING'] = (string) $query;

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

        $response = Yii::$app->getResponse();

        if (Yii::$app->getRequest()->enableCookieValidation) {
            $validationKey = Yii::$app->getRequest()->cookieValidationKey;
        }

        /** @var \yii\web\Cookie $cookie */
        foreach ($response->getCookies() as $cookie) {
            $value = $cookie->value;

            if (1 !== $cookie->expire && isset($validationKey)) {
                $data = [$cookie->name, $cookie->value];
                $value = Yii::$app->getSecurity()->hashData(serialize($data), $validationKey);
            }

            $expires = is_int($cookie->expire) ? (string) $cookie->expire : null;

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

        $content = ob_get_clean();

        $status = $response->getStatusCode();
        $headers = $response->getHeaders()->toArray();

        $response->clear();

        return new Response($content, $status, $headers);
    }

    private function normalizeQueryParameters(array $parameters): array
    {
        parse_str(http_build_query($parameters), $normalizedParameters);
        return $normalizedParameters;
    }
}
