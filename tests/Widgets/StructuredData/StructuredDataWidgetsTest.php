<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\StructuredData;

use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\StructuredData\BreadcrumbList;
use Hirtz\Skeleton\Widgets\StructuredData\Event;
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

    public function testAnEventNeedsAStartAndAPlace(): void
    {
        $start = new DateTime('2026-10-09 18:00:00', new \DateTimeZone('UTC'));

        self::assertNull(Event::make()->location('Hall')->build());
        self::assertNull(Event::make()->startDate($start)->build());
        self::assertNull(Event::make()->startDate($start)->location('')->build());
        self::assertNotNull(Event::make()->startDate($start)->location('Hall')->build());
        self::assertNotNull(Event::make()->startDate($start)->virtualLocation('https://example.com/stream')->build());
    }

    public function testAnEvent(): void
    {
        $node = Event::make()
            ->startDate(new DateTime('2026-10-09 18:00:00', new \DateTimeZone('UTC')))
            ->endDate(new Date('2026-10-11'))
            ->eventStatus(Event::STATUS_POSTPONED)
            ->location('Hall', 'Mu' . "\u{308}" . 'llerstraße 1')
            ->virtualLocation('https://example.com/stream')
            ->offer('https://example.com/tickets', '19.90', 'EUR')
            ->organizer(Organization::getDefaultId())
            ->addProperties(['name' => 'Å concert'])
            ->build();

        self::assertSame([
            '@type' => 'Event',
            'startDate' => '2026-10-09T18:00:00+00:00',
            'endDate' => '2026-10-11',
            'eventStatus' => 'https://schema.org/EventPostponed',
            'eventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode',
            'location' => [
                ['@type' => 'Place', 'name' => 'Hall', 'address' => 'Mu' . "\u{308}" . 'llerstraße 1'],
                ['@type' => 'VirtualLocation', 'url' => 'https://example.com/stream'],
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => 'https://example.com/tickets',
                'price' => '19.90',
                'priceCurrency' => 'EUR',
            ],
            'organizer' => ['@id' => 'https://www.test.localhost/#organization'],
            'name' => 'Å concert',
        ], $node);
    }

    public function testAnOnlineEventAtANodeOfItsOwn(): void
    {
        $online = Event::make()
            ->startDate(new DateTime('2026-10-09 18:00:00'))
            ->virtualLocation('https://example.com/stream')
            ->build();

        self::assertSame('https://schema.org/OnlineEventAttendanceMode', $online['eventAttendanceMode'] ?? null);

        $place = Thing::make()->type('Place')->properties(['name' => 'Hall']);
        $node = Event::make()->startDate(new DateTime('2026-10-09 18:00:00'))->location($place)->build();

        self::assertSame(['@type' => 'Place', 'name' => 'Hall'], $node['location'] ?? null);
        self::assertSame('https://schema.org/OfflineEventAttendanceMode', $node['eventAttendanceMode'] ?? null);
    }

    private function getView(): View
    {
        $view = Yii::$app->getView();
        self::assertInstanceOf(View::class, $view);

        return $view;
    }
}
