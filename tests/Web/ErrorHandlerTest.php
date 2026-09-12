<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;

/**
 * The 404 handler resolves a redirect by the request path, host-qualified first.
 */
final class ErrorHandlerTest extends TestCase
{
    use FunctionalTestTrait;

    public function testBarePathRedirectResolves(): void
    {
        $this->createRedirect('old-page', 'new-page');

        $this->open('/old-page');

        self::assertCurrentUrlEquals('/new-page');
    }

    public function testHostQualifiedRedirectResolvesOnItsHost(): void
    {
        $this->createRedirect('www.test.localhost/old-page', 'new-page');

        $this->open('/old-page');

        self::assertCurrentUrlEquals('/new-page');
    }

    public function testHostQualifiedRedirectDoesNotResolveOnAnotherHost(): void
    {
        $this->createRedirect('www.other.localhost/old-page', 'new-page');

        $this->open('/old-page');

        self::assertCurrentUrlEquals('/old-page');
        self::assertResponseStatusCodeSame(404);
    }

    public function testHostQualifiedRedirectWinsOverBarePath(): void
    {
        $this->createRedirect('old-page', 'global-page');
        $this->createRedirect('www.test.localhost/old-page', 'host-page');

        $this->open('/old-page');

        self::assertCurrentUrlEquals('/host-page');
    }

    protected function createRedirect(string $requestUri, string $url): void
    {
        $redirect = Redirect::create();
        $redirect->request_uri = $requestUri;
        $redirect->url = $url;

        self::assertTrue($redirect->insert(), implode(' ', $redirect->getErrorSummary(true)));
    }
}
