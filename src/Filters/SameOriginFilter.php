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
        $site = is_string($site) ? $site : '';

        if (in_array($site, ['same-origin', 'none'], true)) {
            return true;
        }

        $origin = $headers->get('Origin');
        $origin = is_string($origin) ? $origin : '';

        // A sibling host sends `same-site`, so a trusted origin is checked before Fetch Metadata refuses it.
        if ($origin !== '' && in_array($origin, $this->trustedOrigins, true)) {
            return true;
        }

        if ($site !== '') {
            return false;
        }

        return $origin === '' || $origin === $request->getHostInfo();
    }
}
