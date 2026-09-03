<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Routing;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Routing\Route;
use Hirtz\Skeleton\Test\TestCase;
use InvalidArgumentException;
use Yii;

class UrlGeneratorTest extends TestCase
{
    public function testUrlManagerIsTheGenerator(): void
    {
        self::assertSame(Yii::$app->getUrlManager(), Yii::$app->getUrlGenerator());
    }

    public function testGenerateFromDefaultRoutes(): void
    {
        $generator = Yii::$app->getUrlGenerator();

        self::assertTrue($generator->hasRoute('health'));
        self::assertSame('/application-health', $generator->generate('health'));
        self::assertSame('/sitemap.xml', $generator->generate('sitemap'));
    }

    public function testGenerateWithPlaceholder(): void
    {
        Yii::$app->addRoutes(
            Route::to('shop/{slug}', 'shop/site/view')
                ->where('slug', Route::PATTERN_PATH)
                ->withoutParamEncoding()
                ->name('test.shop.view')
        );

        self::assertSame('/shop/foo/bar', Yii::$app->getUrlGenerator()->generate('test.shop.view', [
            'slug' => 'foo/bar',
        ]));
    }

    public function testUnmatchedParamsBecomeQueryString(): void
    {
        self::assertSame(
            '/application-health?page=2',
            Yii::$app->getUrlGenerator()->generate('health', ['page' => 2])
        );
    }

    public function testPlaceholderInActionIsSubstituted(): void
    {
        Yii::$app->addRoutes(Route::to('api/{action}.json', 'test/api/{action}')->name('test.api'));

        self::assertSame('/api/index.json', Yii::$app->getUrlGenerator()->generate('test.api', [
            'action' => 'index',
        ]));
    }

    /**
     * A default makes the placeholder optional: it is omitted from the path when the value matches.
     */
    public function testDefaultsMakePlaceholderOptional(): void
    {
        Yii::$app->addRoutes(
            Route::to('feed/{format}', 'test/feed/index')
                ->defaults(['format' => 'rss'])
                ->name('test.feed')
        );

        $generator = Yii::$app->getUrlGenerator();

        self::assertSame('/feed', $generator->generate('test.feed'));
        self::assertSame('/feed/atom', $generator->generate('test.feed', ['format' => 'atom']));
    }

    public function testGenerateAbsolute(): void
    {
        $url = Yii::$app->getUrlGenerator()->generateAbsolute('health', [], 'https');

        self::assertStringStartsWith('https://', $url);
        self::assertStringEndsWith('/application-health', $url);
    }

    public function testUnknownNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Yii::$app->getUrlGenerator()->generate('nope');
    }

    public function testMissingPlaceholderValueThrows(): void
    {
        Yii::$app->addRoutes(Route::to('x/{action}', 'test/x/{action}')->name('test.missing'));

        $this->expectException(InvalidArgumentException::class);

        Yii::$app->getUrlGenerator()->generate('test.missing');
    }

    public function testRegisteredRouteIsUnaffectedByLaterMutation(): void
    {
        $route = Route::to('mutate/{id}', 'test/mutate/view')
            ->where('id', Route::PATTERN_ID)
            ->name('test.mutate');

        Yii::$app->addRoutes($route);

        $route->name('changed')->position(Route::POSITION_FIRST);

        $registered = Yii::$app->getRoutes()->getByName('test.mutate');

        self::assertNotNull($registered);
        self::assertSame('test.mutate', $registered->name);
        self::assertSame(Route::POSITION_DEFAULT, $registered->position);
    }

    public function testUrlRouteHelperDelegatesToGenerator(): void
    {
        self::assertSame('/application-health', Url::route('health'));
        self::assertStringStartsWith('https://', Url::route('health', [], 'https'));
    }

    public function testUrlToStillAcceptsYiiRouteArrays(): void
    {
        self::assertSame('/application-health', Url::to(['/health/index']));
    }
}
