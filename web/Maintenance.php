<?php

namespace davidhirtz\yii2\skeleton\web;

use yii\web\Application;
use yii\base\BaseObject;
use yii\base\BootstrapInterface;
use yii\web\HttpException;

/**
 * Class Maintenance
 * @package davidhirtz\yii2\skeleton\web
 *
 * To enable maintenance mode either set application config parameter `maintenance` to `true` or configure this
 * application component with `enabled` set to `true`. The second option is useful if you want to shut down the
 * application for a longer time and present a more accurate `retryAfter` value.
 */
class Maintenance extends BaseObject implements BootstrapInterface
{
    /**
     * @var bool whether maintenance mode should be activated via config.
     */
    public $enabled = false;

    /**
     * @var string the maintenance route which will be used as `catchAll` action. If route is not set an HTTP
     * exception with given `statusCode` will be thrown.
     */
    public $route;

    /**
     * @var string[] containing routes that should stay available during maintenance. Mark routes with an trailing
     * wildcard (*) if needed. Application health (health/index) and the debug panel (if enabled) are excluded by
     * default.
     */
    public $excludedRoutes = [];

    /**
     * @var int status code to send on maintenance, defaults to 503 Service Unavailable
     */
    public $statusCode = 503;

    /**
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.37
     * @var bool|int|string timespan in seconds or date string (eg. Fri, 31 Dec 2020 23:59:59 GMT)
     */
    public $retryAfter = 30;

    /**
     * Fires on {@link Application::bootstrap()} if `enabled` is set to `true`.
     * @param Application $app
     */
    public function bootstrap($app): void
    {
        $response = $app->getResponse();
        $route = $app->getRequest()->resolve()[0];

        if (!$this->isRouteExcluded($route)) {
            $response->setStatusCode($this->statusCode);

            if ($this->retryAfter) {
                $response->getHeaders()->set('Retry-After', $this->retryAfter);
            }

            if (!$this->route) {
                // Makes sure errors are not handled by action which would throw an error while handling the exception
                $app->getErrorHandler()->errorAction = null;
                throw new HttpException($this->statusCode);
            }

            $app->catchAll = [$this->route];
        }
    }

    /**
     * @param string $route
     * @return bool
     */
    protected function isRouteExcluded($route): bool
    {
        $excludedRoutes = array_filter(array_merge($this->excludedRoutes,
            ['health/index'],
            YII_DEBUG ? ['debug', 'debug/default*', 'debug/user*'] : [],
        ));

        foreach ($excludedRoutes as $excluded) {
            if ($excluded == $route || ($excluded[-1] === '*' && substr($excluded, 0, -1) == substr($route, 0, strlen($excluded) - 1))) {
                return true;
            }
        }

        return false;
    }
}