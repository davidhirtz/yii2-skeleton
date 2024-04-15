<?php

namespace davidhirtz\yii2\skeleton\tests\unit\web;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\helpers\StructuredData;
use davidhirtz\yii2\skeleton\web\Controller;
use davidhirtz\yii2\skeleton\web\View;
use Yii;

class ViewTest extends Unit
{
    public function testBreadcrumbs(): void
    {
        $view = new View();

        $view->setBreadcrumbs([
            'Home' => '/',
            'No link',
        ]);

        $expected = [
            [
                'label' => 'Home',
                'url' => '/',
            ],
            [
                'label' => 'No link',
                'url' => null
            ],
        ];

        static::assertEquals($expected, $view->getBreadcrumbs());

        $html = StructuredData::breadcrumbList($view->getBreadcrumbs());
        static::assertStringContainsString('<script type="application/ld+json">', $html);
    }

    public function testHrefLangLinkTags(): void
    {
        Yii::$app->controller = new Controller('test', Yii::$app);

        $view = new View();
        $view->registerHrefLangLinkTags(['en', 'de'], false);

        $expected = [
            'hreflang_en' => '<link href="https://www.example.com/test?language=en" rel="alternate" hreflang="en">',
            'hreflang_de' => '<link href="https://www.example.com/test?language=de" rel="alternate" hreflang="de">',
        ];

        static::assertEquals($expected, $view->linkTags);
    }

    public function testOpenGraphMetaTags(): void
    {
        $title = 'Default Title';
        $description = 'Default Description';

        $view = new View();

        $view->setTitle($title);
        $view->setMetaDescription($description);

        $view->registerOpenGraphMetaTags();

        static::assertEquals("<meta name=\"og:title\" content=\"$title\">", $view->metaTags['og:title']);
        static::assertEquals("<meta name=\"og:description\" content=\"$description\">", $view->metaTags['og:description']);
    }

    public function testImageMetaTags(): void
    {
        $view = new View();
        $view->registerImageMetaTags('/images/test.jpg', 300, 200);
        $view->registerImageMetaTags('/images/test-2.jpg');

        static::assertEquals("<meta property=\"og:image\" content=\"https://www.example.com/images/test.jpg\">", $view->metaTags[0]);
        static::assertEquals("<meta property=\"og:image:width\" content=\"300\">", $view->metaTags[1]);
        static::assertEquals("<meta property=\"og:image:height\" content=\"200\">", $view->metaTags[2]);
        static::assertContains('<link href="https://www.example.com/images/test-2.jpg" rel="image_src">', $view->linkTags);
    }

    public function testCanonicalTag(): void
    {
        $view = new View();
        $view->registerCanonicalTag('/test');

        static::assertContains('<link href="https://www.example.com/test" rel="canonical">', $view->linkTags);
    }

    public function testFilenameWithVersion(): void
    {
        $filename = '/tests/bootstrap.php';
        $time = filemtime(Yii::getAlias('@webroot') . $filename);

        $view = new View();
        $actual = $view->getFilenameWithVersion($filename);
        static::assertEquals("$filename?$time", $actual);
    }
}
