<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Routing;

use Hirtz\Skeleton\Routing\Compilers\YiiRouteCompiler;
use Hirtz\Skeleton\Routing\Route;
use Hirtz\Skeleton\Routing\RouteCollection;
use Hirtz\Skeleton\Test\TestCase;
use InvalidArgumentException;
use yii\web\UrlRule;

class YiiRouteCompilerTest extends TestCase
{
    public function testCompilePlaceholders(): void
    {
        $route = Route::to('api/location/{action}.{format}', 'location/api/{action}');

        self::assertSame([
            'pattern' => 'api/location/<action>.<format>',
            'route' => 'location/api/<action>',
            'position' => Route::POSITION_DEFAULT,
        ], $this->compile($route));
    }

    public function testCompileConstrainedPlaceholder(): void
    {
        $route = Route::to('{slug}', 'cms/site/view')
            ->where('slug', Route::PATTERN_PATH)
            ->withoutParamEncoding()
            ->position(Route::POSITION_FALLBACK);

        self::assertSame([
            'pattern' => '<slug:.+>',
            'route' => 'cms/site/view',
            'position' => Route::POSITION_FALLBACK,
            'encodeParams' => false,
        ], $this->compile($route));
    }

    public function testCompileRawPatternIsNotRewritten(): void
    {
        $route = Route::raw('admin/?', 'admin/');

        self::assertSame('admin/?', $this->compile($route)['pattern']);
    }

    public function testCompileVerbsAndMode(): void
    {
        $route = Route::to('shopify/webhook/{action}', 'shopify/webhook/{action}')
            ->methods('post')
            ->parseOnly();

        $declaration = $this->compile($route);

        self::assertSame(['POST'], $declaration['verb']);
        self::assertSame(UrlRule::PARSING_ONLY, $declaration['mode']);
    }

    public function testCompileCustomRuleClass(): void
    {
        $route = Route::to('{category}/{slug}', 'cms/site/view')
            ->where('category', Route::PATTERN_SEGMENT)
            ->where('slug', Route::PATTERN_PATH)
            ->useRuleClass(UrlRule::class, ['paramName' => 'category']);

        $declaration = $this->compile($route);

        self::assertSame(UrlRule::class, $declaration['class']);
        self::assertSame('category', $declaration['paramName']);
        self::assertSame('<category:[^\/]+>/<slug:.+>', $declaration['pattern']);
    }

    public function testCompilePreservesRouteNameOutOfTheRule(): void
    {
        $declaration = $this->compile(Route::to('sitemap.xml', 'sitemap/index')->name('sitemap'));

        self::assertArrayNotHasKey('name', $declaration);
    }

    public function testConstraintOnUndefinedPlaceholderThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new YiiRouteCompiler())->compile(Route::to('static', 'site/index')->where('slug', '.+'));
    }

    public function testCollectionSortsByPositionAndKeepsRegistrationOrder(): void
    {
        $collection = new RouteCollection();
        $collection->add(
            Route::to('a', 'site/a')->last()->name('a'),
            Route::to('b', 'site/b')->first()->name('b'),
            Route::to('c', 'site/c')->name('c'),
            Route::to('d', 'site/d')->name('d'),
        );

        self::assertSame(['b', 'c', 'd', 'a'], array_map(
            static fn (Route $route): ?string => $route->name,
            $collection->all()
        ));

        self::assertSame('site/b', $collection->getByName('b')?->action);
    }

    /**
     * A bundle bootstrapped twice — via `extra.bootstrap` and an application's `bootstrap` config —
     * must not register its routes twice.
     */
    public function testAddingAnIdenticalRouteTwiceIsANoop(): void
    {
        $collection = new RouteCollection();
        $named = Route::to('{slug}', 'cms/site/view')->where('slug', Route::PATTERN_PATH)->name('cms.view');
        $anonymous = Route::to('sitemap.xml', 'sitemap/index');

        self::assertCount(2, $collection->add($named, $anonymous));
        self::assertSame([], $collection->add($named, $anonymous));
        self::assertCount(2, $collection);
    }

    public function testConflictingRouteNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RouteCollection())->add(
            Route::to('a', 'site/a')->name('duplicate'),
            Route::to('b', 'site/b')->name('duplicate'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(Route $route): array
    {
        return (new YiiRouteCompiler())->compile($route)[0];
    }
}
