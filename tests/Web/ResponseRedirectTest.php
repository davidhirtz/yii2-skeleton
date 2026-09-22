<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use yii\helpers\Json;

/**
 * `HX-Location` is htmx's navigation, and it carries a swap context that overrides the `hx-select`, `hx-swap` and
 * `hx-select-oob` of whatever issued the request — so an action refreshing a region of the page the user is on has
 * to opt out of it. Which of the two a redirect is only the action knows: a form targets itself so its validation
 * errors land in place, and still navigates away once it saves.
 */
class ResponseRedirectTest extends TestCase
{
    public function testAnOrdinaryRequestIsRedirectedWithALocationHeader(): void
    {
        $headers = $this->getWebResponse()
            ->redirect(['/admin/dashboard/index'])
            ->getHeaders();

        self::assertNull($headers->get('HX-Location'));
        self::assertStringEndsWith('/admin/dashboard/index', (string)$headers->get('Location'));
    }

    public function testAnHtmxRequestIsNavigatedWithTheTargetTheActionNames(): void
    {
        $this->getWebRequest()->getHeaders()->set('HX-Request', 'true');

        $response = $this->getWebResponse();
        $headers = $response->redirect(['/admin/dashboard/index'])->getHeaders();

        self::assertSame(200, $response->getStatusCode());
        self::assertNull($headers->get('Location'));

        $location = Json::decode((string)$headers->get('HX-Location'));

        self::assertIsArray($location);
        self::assertSame('#wrap', $location['target']);
        self::assertStringEndsWith('/admin/dashboard/index', (string)$location['path']);
    }

    /**
     * `HX-Redirect` is the browser's own navigation, which is what throws away everything a swap leaves standing.
     */
    public function testAnActionEndingTheDocumentIsRedirectedWithABrowserNavigation(): void
    {
        $this->getWebRequest()->getHeaders()->set('HX-Request', 'true');

        $response = $this->getWebResponse();
        $headers = $response->setHtmxReload()
            ->redirect(['/admin/dashboard/index'])
            ->getHeaders();

        self::assertSame(200, $response->getStatusCode());
        self::assertNull($headers->get('HX-Location'));
        self::assertNull($headers->get('Location'));
        self::assertStringEndsWith('/admin/dashboard/index', (string)$headers->get('HX-Redirect'));
    }

    /**
     * The flag is inert outside htmx, so an action need not ask which kind of request it is answering.
     */
    public function testAnOrdinaryRequestIsRedirectedWithALocationHeaderEvenSo(): void
    {
        $response = $this->getWebResponse();
        $headers = $response->setHtmxReload()
            ->redirect(['/admin/dashboard/index'])
            ->getHeaders();

        self::assertSame(302, $response->getStatusCode());
        self::assertNull($headers->get('HX-Redirect'));
        self::assertStringEndsWith('/admin/dashboard/index', (string)$headers->get('Location'));
    }

    /**
     * Without a target the response says nothing about the swap, so the requesting element follows the redirect
     * itself and the attributes it declares apply to what comes back.
     */
    public function testAnActionRefreshingAPartOfThePageOptsOutOfTheNavigation(): void
    {
        $this->getWebRequest()->getHeaders()->set('HX-Request', 'true');

        $response = $this->getWebResponse();
        $headers = $response->setHtmxRedirectTarget(null)
            ->redirect(['/admin/dashboard/index'])
            ->getHeaders();

        self::assertSame(302, $response->getStatusCode());
        self::assertNull($headers->get('HX-Location'));
        self::assertStringEndsWith('/admin/dashboard/index', (string)$headers->get('Location'));
    }
}
