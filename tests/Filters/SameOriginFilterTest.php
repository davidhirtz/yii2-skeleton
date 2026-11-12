<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Filters;

use Hirtz\Skeleton\Filters\SameOriginFilter;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Controller;
use yii\base\InlineAction;
use yii\web\ForbiddenHttpException;

class SameOriginFilterTest extends TestCase
{
    public function testAGetRequestPasses(): void
    {
        $this->getWebRequest()->getHeaders()->set('Sec-Fetch-Site', 'cross-site');

        self::assertTrue((new SameOriginFilter())->isSameOrigin());
    }

    public function testFetchMetadataDecides(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $headers = $this->getWebRequest()->getHeaders();
        $filter = new SameOriginFilter();

        foreach (['same-origin' => true, 'none' => true, 'same-site' => false, 'cross-site' => false] as $site => $expected) {
            $headers->set('Sec-Fetch-Site', $site);
            $headers->set('Origin', $this->getWebRequest()->getHostInfo());
            self::assertSame($expected, $filter->isSameOrigin(), $site);
        }
    }

    public function testTheOriginDecidesWithoutFetchMetadata(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $headers = $this->getWebRequest()->getHeaders();
        $filter = new SameOriginFilter();

        $headers->set('Origin', (string)$this->getWebRequest()->getHostInfo());
        self::assertTrue($filter->isSameOrigin());

        $headers->set('Origin', 'https://evil.example');
        self::assertFalse($filter->isSameOrigin());

        $headers->set('Origin', 'null');
        self::assertFalse($filter->isSameOrigin());

        $filter->trustedOrigins = ['https://evil.example'];
        $headers->set('Origin', 'https://evil.example');
        self::assertTrue($filter->isSameOrigin());
    }

    public function testATrustedOriginPassesDespiteFetchMetadata(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $headers = $this->getWebRequest()->getHeaders();
        $filter = new SameOriginFilter(['trustedOrigins' => ['https://shop.example.com']]);

        $headers->set('Sec-Fetch-Site', 'same-site');
        $headers->set('Origin', 'https://shop.example.com');
        self::assertTrue($filter->isSameOrigin());

        $headers->set('Origin', 'https://blog.example.com');
        self::assertFalse($filter->isSameOrigin());

        $headers->remove('Origin');
        self::assertFalse($filter->isSameOrigin());

        $headers->set('Sec-Fetch-Site', 'cross-site');
        $headers->set('Origin', 'https://shop.example.com.evil.example');
        self::assertFalse($filter->isSameOrigin());
    }

    public function testARequestWithNeitherHeaderIsNoBrowserAndPasses(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        self::assertTrue((new SameOriginFilter())->isSameOrigin());
    }

    public function testACrossOriginPostIsForbiddenAndASameOriginOneSkipsTheCsrfToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller = new Controller('site', Application::current());
        $action = new InlineAction('index', $controller, 'actionIndex');

        $this->getWebRequest()->getHeaders()->set('Sec-Fetch-Site', 'same-origin');
        self::assertTrue((new SameOriginFilter())->beforeAction($action));
        self::assertFalse($controller->enableCsrfValidation);

        $this->getWebRequest()->getHeaders()->set('Sec-Fetch-Site', 'cross-site');
        $this->expectException(ForbiddenHttpException::class);
        (new SameOriginFilter())->beforeAction($action);
    }
}
