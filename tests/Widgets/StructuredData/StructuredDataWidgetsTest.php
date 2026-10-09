<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\StructuredData;

use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\StructuredData\BreadcrumbList;
use Hirtz\Skeleton\Widgets\StructuredData\Organization;
use Hirtz\Skeleton\Widgets\StructuredData\Thing;
use Hirtz\Skeleton\Widgets\StructuredData\WebSite;
use Yii;

class StructuredDataWidgetsTest extends TestCase
{
    public function testAThingIsAnyTypeWithItsProperties(): void
    {
        $node = Thing::make()
            ->type('Event')
            ->id('https://www.test.localhost/concert#event')
            ->properties(['name' => 'Concert', 'description' => null])
            ->addProperties(['startDate' => '2026-10-09'])
            ->getNode();

        self::assertSame([
            '@type' => 'Event',
            '@id' => 'https://www.test.localhost/concert#event',
            'name' => 'Concert',
            'startDate' => '2026-10-09',
        ], $node);
    }

    public function testAThingWithoutATypeRegistersNothing(): void
    {
        Thing::make()->properties(['name' => 'Nothing'])->register();
        self::assertSame([], $this->getView()->getStructuredData());
    }

    public function testRegisteringAgainReplacesTheNodeWithTheSameId(): void
    {
        Thing::make()->type('Event')->id('https://www.test.localhost/#event')->properties(['name' => 'Old'])->register();
        Thing::make()->type('Event')->id('https://www.test.localhost/#event')->properties(['name' => 'New'])->register();
        Thing::make()->type('Place')->properties(['name' => 'Anonymous'])->register();

        $nodes = $this->getView()->getStructuredData();

        self::assertCount(2, $nodes);
        self::assertSame('New', $nodes['https://www.test.localhost/#event']['name'] ?? null);
    }

    public function testRenderedOnItsOwnAThingIsAScript(): void
    {
        self::assertSame(
            '<script type="application/ld+json">{"@context":"https:\/\/schema.org","@type":"Place","name":"Å"}</script>',
            Thing::make()->type('Place')->properties(['name' => 'Å'])->render(),
        );
    }

    public function testAnOrganizationWithoutANameRegistersNothing(): void
    {
        Organization::make()->register();
        self::assertSame([], $this->getView()->getStructuredData());
    }

    public function testTheOrganizationIsConfiguredThroughTheContainer(): void
    {
        Yii::$container->set(Organization::class, [
            'name' => 'Example GmbH',
            'logo' => '/logo.png',
            'sameAs' => ['https://www.instagram.com/example'],
        ]);

        Organization::make()->register();

        self::assertSame([
            Organization::getDefaultId() => [
                '@type' => 'Organization',
                '@id' => 'https://www.test.localhost/#organization',
                'name' => 'Example GmbH',
                'url' => 'https://www.test.localhost/',
                'logo' => ['@type' => 'ImageObject', 'url' => 'https://www.test.localhost/logo.png'],
                'sameAs' => ['https://www.instagram.com/example'],
            ],
        ], $this->getView()->getStructuredData());
    }

    public function testTheWebSiteIsNamedAfterTheApplicationAndPointsToItsPublisher(): void
    {
        $node = WebSite::make()
            ->publisher(Organization::getDefaultId());

        $node->register();

        self::assertSame([
            '@type' => 'WebSite',
            '@id' => 'https://www.test.localhost/#website',
            'name' => Yii::$app->name,
            'url' => 'https://www.test.localhost/',
            'inLanguage' => Yii::$app->language,
            'publisher' => ['@id' => 'https://www.test.localhost/#organization'],
        ], $node->getNode());
    }

    public function testABreadcrumbListJoinsTheGraph(): void
    {
        BreadcrumbList::make()
            ->id('https://www.test.localhost/shoes/red#breadcrumb')
            ->breadcrumbs([new Breadcrumb('Shoes', '/shoes'), new Breadcrumb('Red')])
            ->register();

        self::assertSame([
            'https://www.test.localhost/shoes/red#breadcrumb' => [
                '@type' => 'BreadcrumbList',
                '@id' => 'https://www.test.localhost/shoes/red#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Shoes', 'item' => 'https://www.test.localhost/shoes'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Red'],
                ],
            ],
        ], $this->getView()->getStructuredData());
    }

    private function getView(): View
    {
        $view = Yii::$app->getView();
        self::assertInstanceOf(View::class, $view);

        return $view;
    }
}
