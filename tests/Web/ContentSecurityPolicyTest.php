<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Web\ContentSecurityPolicy;
use Hirtz\Skeleton\Web\View;

/**
 * The admin allows only the scripts it stamps with the page's nonce; a frontend page, which may be cached for every
 * visitor, restricts nothing it runs and carries no nonce.
 */
final class ContentSecurityPolicyTest extends TestCase
{
    use FunctionalTestTrait;

    public function testThePolicyCarriesTheNonce(): void
    {
        $policy = new ContentSecurityPolicy();
        $nonce = $policy->getNonce();

        self::assertSame($nonce, $policy->getNonce());
        self::assertSame(
            "script-src 'self' 'strict-dynamic' 'nonce-$nonce'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'",
            $policy->getPolicy(),
        );
    }

    public function testSourcesAreAddedOnceAndDirectivesRemoved(): void
    {
        $policy = new ContentSecurityPolicy();

        $policy->addSource('frame-src', 'https://www.youtube-nocookie.com', "'self'")
            ->addSource('frame-src', "'self'")
            ->setDirective('base-uri', null);

        self::assertSame(["'self'", "'strict-dynamic'"], $policy->directives['script-src']);
        self::assertSame(['https://www.youtube-nocookie.com', "'self'"], $policy->directives['frame-src']);
        self::assertArrayNotHasKey('base-uri', $policy->directives);
    }

    public function testEveryScriptOfTheViewCarriesTheNonce(): void
    {
        $view = new View();
        $view->nonce = 'abc';

        $view->registerJs('head();', View::POS_HEAD);
        $view->registerJs('begin();', View::POS_BEGIN);
        $view->registerJs('end();', View::POS_END);
        $view->registerJsFile('/file.js', ['position' => View::POS_END]);
        $view->registerJsModule('/module.js');

        $html = $this->renderPage($view);

        preg_match_all('/<script[^>]*>/', $html, $matches);

        self::assertCount(5, $matches[0], $html);

        foreach ($matches[0] as $tag) {
            self::assertStringContainsString('nonce="abc"', $tag);
        }
    }

    public function testAViewWithoutANonceRendersAsBefore(): void
    {
        $view = new View();
        $view->registerJs('end();', View::POS_END);
        $view->registerJsFile('/file.js');

        $html = $this->renderPage($view);

        self::assertStringContainsString('<script src="/file.js"></script>', $html);
        self::assertStringContainsString("<script>end();</script>", $html);
        self::assertStringNotContainsString('nonce', $html);
    }

    public function testTheAdminSendsTheStrictPolicy(): void
    {
        $this->open('admin/account/login');

        $header = self::$client->getResponse()->getHeader('Content-Security-Policy');
        self::assertIsString($header);

        preg_match("/'nonce-([^']+)'/", $header, $matches);
        $nonce = $matches[1] ?? self::fail($header);

        self::assertStringContainsString("script-src 'self' 'strict-dynamic'", $header);

        $scripts = self::$crawler->filter('script');
        self::assertGreaterThan(0, $scripts->count());

        foreach ($scripts->extract(['nonce']) as $attribute) {
            self::assertSame($nonce, $attribute);
        }
    }

    public function testAFrontendPageRestrictsNothingItRuns(): void
    {
        $this->open('/sitemap.xml');

        self::assertSame(
            "frame-ancestors 'self'; object-src 'none'; base-uri 'self'",
            self::$client->getResponse()->getHeader('Content-Security-Policy'),
        );
    }

    private function renderPage(View $view): string
    {
        ob_start();
        $view->beginPage();
        $view->head();
        $view->beginBody();
        $view->endBody();
        $view->endPage();

        return (string)ob_get_clean();
    }
}
