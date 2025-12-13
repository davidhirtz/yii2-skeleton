<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Yii;

class SitemapTest extends TestCase
{
    use FunctionalTestTrait;

    public function testSitemapXML(): void
    {
        $this->open('sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/xml');
        self::assertFalse(Yii::$app->getRequest()->getIsDraft());
    }
}
