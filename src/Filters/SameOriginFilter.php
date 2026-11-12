<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Filters;

use Hirtz\Skeleton\Web\Application;
use Override;
use yii\base\ActionFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * Refuses a cross-origin request by the headers a browser sends itself, so a page-cached form needs no CSRF token.
 */
class SameOriginFilter extends ActionFilter
{
    /**
     * Origins other than the request's own allowed to post, such as `https://www.example.com`.
     *
     * @var list<string>
     */
    public array $trustedOrigins = [];

    public bool $disableCsrfValidation = true;

    #[Override]
    public function beforeAction($action): bool
    {
        if (!$this->isSameOrigin()) {
            throw new ForbiddenHttpException();
        }

        if ($this->disableCsrfValidation && $action->controller instanceof Controller) {
            $action->controller->enableCsrfValidation = false;
        }

        return true;
    }

    public function isSameOrigin(): bool
    {
        $request = Application::current()->getRequest();

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $headers = $request->getHeaders();
        $site = $headers->get('Sec-Fetch-Site');

        if (is_string($site) && $site !== '') {
            return in_array($site, ['same-origin', 'none'], true);
        }

        $origin = $headers->get('Origin');

        if (!is_string($origin) || $origin === '') {
            return true;
        }

        return in_array($origin, [$request->getHostInfo(), ...$this->trustedOrigins], true);
    }
}
